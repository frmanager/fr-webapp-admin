<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Grade;
use AppBundle\Entity\Student;
use AppBundle\Entity\Classroom;
use AppBundle\Utils\CSVHelper;
use AppBundle\Utils\CampaignHelper;
use AppBundle\Entity\Donation;
use AppBundle\Utils\ValidationHelper;
use AppBundle\Utils\DonationHelper;
use \DateTime;
use \DateTimeZone;

/**
 * Donation controller.
 *
 * @Route("{campaignUrl}/donations")
 */
class DonationController extends Controller
{
    /**
     * Lists all Donation entities.
     *
     * @Route("/", name="donation_index")
     * @Method("GET")
     */
    public function indexAction($campaignUrl)
    {
      $logger = $this->get('logger');
      $em = $this->getDoctrine()->getManager();

      //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
      $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
      if(is_null($campaign)){
        $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this campaign.');
        return $this->redirectToRoute('homepage');
      }

      //CODE TO CHECK TO SEE IF USER HAS PERMISSIONS TO CAMPAIGN
      $campaignHelper = new CampaignHelper($em, $logger);
      if(!$campaignHelper->campaignPermissionsCheck($this->get('security.token_storage')->getToken()->getUser(), $campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'You do not have permissions to this campaign.');
          return $this->redirectToRoute('homepage');
      }


        $donations = $em->getRepository('AppBundle:Donation')->findByCampaign($campaign);

        return $this->render('donation/donation.index.html.twig', array(
            'donations' => $donations,
            'campaign' => $campaign
        ));
    }

    /**
     * Creates a new Donation entity.
     *
     * @Route("/new", name="donation_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, $campaignUrl)
    {
      $logger = $this->get('logger');
      $em = $this->getDoctrine()->getManager();

      //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
      $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
      if(is_null($campaign)){
        $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this campaign.');
        return $this->redirectToRoute('homepage');
      }

      //CODE TO CHECK TO SEE IF USER HAS PERMISSIONS TO CAMPAIGN
      $campaignHelper = new CampaignHelper($em, $logger);
      if(!$campaignHelper->campaignPermissionsCheck($this->get('security.token_storage')->getToken()->getUser(), $campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'You do not have permissions to this campaign.');
          return $this->redirectToRoute('homepage');
      }

      $donation = new Donation();
      $classroom = null;

      if(null !== $request->query->get('type')){
          $type = $request->query->get('type');

          if(!in_array($type, array('classroom','student','campaign','team'))){
            $this->get('session')->getFlashBag()->add('warning', $type.' is not a valid donation type');
            return $this->redirectToRoute('donation_index', array('campaignUrl'=>$campaign.url));
          }

      }else{
        $this->get('session')->getFlashBag()->add('warning', 'You need to select a donation type');
        return $this->redirectToRoute('donation_index', array('campaignUrl'=>$campaign.url));
      }

      if ($request->isMethod('POST')) {
          $params = $request->request->all();
          $failure = false;

          if(!empty($params['setClassroomFlag'])){
            $donation->setClassroom($em->getRepository('AppBundle:Classroom')->find($params['donation']['classroomID']));
          }

          if($type == 'team'){
            if(null !== $params['donation']['teamID']){
              $team = $em->getRepository('AppBundle:Team')->find($params['donation']['teamID']);
              if(is_null($team)){
                $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this team.');
              }else{
                if(null !== $params['donation']['amount']){
                  $donation->setCreatedBy($this->get('security.token_storage')->getToken()->getUser());
                  $donation->setAmount($params['donation']['amount']);
                  $donation->setType("team");
                  $donation->setTeam($team);
                  $donation->setDonatedAt(new DateTime('now'));
                  $donation->setCampaign($campaign);
                  $donation->setPaymentMethod("cash");
                  $donation->setDonationStatus("ACCEPTED");
                  $donation->setTransactionId(strtoupper(md5(uniqid(rand(), true))));

                  $em->persist($donation);
                  $em->flush();

                  $donationHelper = new DonationHelper($em, $logger);
                  $donationHelper->reloadDonationDatabase(array('donation'=>$donation));


                  $this->get('session')->getFlashBag()->add('success', 'Donation Created Successfully');
                  return $this->redirectToRoute('donation_index', array('campaignUrl'=>$campaign->getUrl()));
                }else{
                  $this->get('session')->getFlashBag()->add('warning', 'Donation Amount is required.');
                }
              }
            }else{
              $this->get('session')->getFlashBag()->add('warning', 'Team is required');
            }
          }elseif($type == 'campaign'){
            if(null !== $params['donation']['amount']){
              $donation->setCreatedBy($this->get('security.token_storage')->getToken()->getUser());
              $donation->setAmount($params['donation']['amount']);
              $donation->setType("campaign");
              $donation->setCampaign($campaign);
              $donation->setDonatedAt(new DateTime('now'));
              $donation->setPaymentMethod("cash");
              $donation->setDonationStatus("ACCEPTED");
              $donation->setTransactionId(strtoupper(md5(uniqid(rand(), true))));

              $em->persist($donation);
              $em->flush();

              $donationHelper = new DonationHelper($em, $logger);
              $donationHelper->reloadDonationDatabase(array('donation'=>$donation));

              $this->get('session')->getFlashBag()->add('success', 'Donation Created Successfully');
              return $this->redirectToRoute('donation_index', array('campaignUrl'=>$campaign->getUrl()));
            }else{
              $this->get('session')->getFlashBag()->add('warning', 'Donation Amount is required.');
            }

          }elseif($type == 'classroom'){
            if(null !== $params['donation']['classroomID']){
              $classroom = $em->getRepository('AppBundle:Classroom')->find($params['donation']['classroomID']);
              if(is_null($classroom)){
                $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this classroom.');
              }else{
                if(null !== $params['donation']['amount']){
                  $donation->setCreatedBy($this->get('security.token_storage')->getToken()->getUser());
                  $donation->setAmount($params['donation']['amount']);
                  $donation->setType("classroom");
                  $donation->setDonatedAt(new DateTime('now'));
                  $donation->setClassroom($classroom);
                  $donation->setCampaign($campaign);
                  $donation->setPaymentMethod("cash");
                  $donation->setDonationStatus("ACCEPTED");
                  $donation->setTransactionId(strtoupper(md5(uniqid(rand(), true))));

                  $em->persist($donation);
                  $em->flush();

                  $donationHelper = new DonationHelper($em, $logger);
                  $donationHelper->reloadDonationDatabase(array('donation'=>$donation));

                  $this->get('session')->getFlashBag()->add('success', 'Donation Created Successfully');
                  return $this->redirectToRoute('donation_index', array('campaignUrl'=>$campaign->getUrl()));
                }else{
                  $this->get('session')->getFlashBag()->add('warning', 'Donation Amount is required.');
                }
              }
            }else{
              $this->get('session')->getFlashBag()->add('warning', 'Classroom is required');
              $failure = true;
            }

          }elseif($type == 'student' && empty($params['setClassroomFlag'])){
            if(null !== $params['donation']['studentID']){
              $student = $em->getRepository('AppBundle:Student')->find($params['donation']['studentID']);
              if(is_null($student)){
                $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this student.');
              }else{
                if(null !== $params['donation']['amount']){
                  $donation->setCreatedBy($this->get('security.token_storage')->getToken()->getUser());
                  $donation->setAmount($params['donation']['amount']);
                  $donation->setType("student");
                  $donation->setStudent($student);
                  $donation->setClassroom($student->getClassroom());
                  $donation->setDonatedAt(new DateTime('now'));
                  $donation->setCampaign($campaign);
                  $donation->setPaymentMethod("cash");
                  $donation->setStudentConfirmedFlag(true);
                  $donation->setDonationStatus("ACCEPTED");
                  $donation->setTransactionId(strtoupper(md5(uniqid(rand(), true))));

                  $em->persist($donation);
                  $em->flush();

                  $donationHelper = new DonationHelper($em, $logger);
                  $donationHelper->reloadDonationDatabase(array('donation'=>$donation));

                  $this->get('session')->getFlashBag()->add('success', 'Donation Created Successfully');
                  return $this->redirectToRoute('donation_index', array('campaignUrl'=>$campaign->getUrl()));
                }else{
                  $this->get('session')->getFlashBag()->add('warning', 'Donation Amount is required.');
                }
              }
            }else{
              $this->get('session')->getFlashBag()->add('warning', 'Student is required');
            }





          }





      }


      return $this->render('donation/donation.form.html.twig', array(
          'donation' => $donation,
          'campaign' => $campaign,
          'teams' => $em->getRepository('AppBundle:Team')->findBy(array('campaign' => $campaign)),
          'type' => $type,
          'classrooms' => $em->getRepository('AppBundle:Classroom')->findBy(array('campaign' => $campaign), array('grade' => 'asc', 'name'=>'asc'))
      ));

    }





    /**
     * Reassignes a donation Entity.
     *
     * @Route("/{donationID}/reassign", name="donation_reassign")
     * @Method({"GET", "POST"})
     */
    public function reassignAction(Request $request, $campaignUrl, $donationID)
    {
      $logger = $this->get('logger');
      $em = $this->getDoctrine()->getManager();

      //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
      $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
      if(is_null($campaign)){
        $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this campaign.');
        return $this->redirectToRoute('homepage');
      }

      //CODE TO CHECK TO SEE IF USER HAS PERMISSIONS TO CAMPAIGN
      $campaignHelper = new CampaignHelper($em, $logger);
      if(!$campaignHelper->campaignPermissionsCheck($this->get('security.token_storage')->getToken()->getUser(), $campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'You do not have permissions to this campaign.');
          return $this->redirectToRoute('homepage');
      }


      //CODE TO CHECK TO SEE IF DONATION EXISTS
      $donation = $em->getRepository('AppBundle:Donation')->find($donationID);
      if(is_null($donation)){
        $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this campaign.');
        return $this->redirectToRoute('donation_index', array('campaignUrl'=>$campaign->getUrl()));
      }


      if(null !== $request->query->get('type')){
          $type = $request->query->get('type');

          if(!in_array($type, array('classroom','student','campaign','team'))){
            $this->get('session')->getFlashBag()->add('warning', $type.' is not a valid donation type');
            return $this->redirectToRoute('donation_index', array('campaignUrl'=>$campaign->getUrl()));
          }

      }else{
        $this->get('session')->getFlashBag()->add('warning', 'You need to select a donation type');
        return $this->redirectToRoute('donation_index', array('campaignUrl'=>$campaign->getUrl()));
      }



      if($type == 'campaign'){
        $donation->setType("campaign");

        $em->persist($donation);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Donation Reassigned Successfully');
        return $this->redirectToRoute('donation_show', array('campaignUrl'=>$campaign->getUrl(), 'id'=>$donation->getId()));
      }


      //Since we are re-assigning, we blank this out till we get the new data
      $donation->setStudent(null);
      $donation->setClassroom(null);
      $donation->setTeam(null);
      $donation->setSTudentConfirmedFlag(false);

      if ($request->isMethod('POST')) {
          $params = $request->request->all();
          $failure = false;

          if(!empty($params['setClassroomFlag'])){
            if("" == $params['donation']['classroomID'] || null == $params['donation']['classroomID']){
              $this->get('session')->getFlashBag()->add('warning', 'Please select a classroom');
            }else{
              //CODE TO CHECK TO SEE IF DONATION EXISTS
              $classroom = $em->getRepository('AppBundle:Classroom')->find($params['donation']['classroomID']);

              if(is_null($classroom)){
                $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this classroom.');
              }else{
                $donation->setClassroom($em->getRepository('AppBundle:Classroom')->find($classroom->getId()));
              }
            }
          }

          if($type == 'team'){
            if(null !== $params['donation']['teamID']){
              $team = $em->getRepository('AppBundle:Team')->find($params['donation']['teamID']);
              if(is_null($team)){
                $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this team.');
              }else{

                  $donation->setTeam($team);
                  $donation->setType("team");

                  $em->persist($donation);
                  $em->flush();

                  $this->get('session')->getFlashBag()->add('success', 'Donation Reassigned Successfully');
                  return $this->redirectToRoute('donation_show', array('campaignUrl'=>$campaign->getUrl(), 'id'=>$donation->getId()));
              }
            }else{
              $this->get('session')->getFlashBag()->add('warning', 'Team is required');
            }
          }elseif($type == 'classroom'){
            if(null !== $params['donation']['classroomID']){
              $classroom = $em->getRepository('AppBundle:Classroom')->find($params['donation']['classroomID']);
              if(is_null($classroom)){
                $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this classroom.');
              }else{
                  $donation->setType("classroom");
                  $donation->setClassroom($classroom);

                  $em->persist($donation);
                  $em->flush();

                  $this->get('session')->getFlashBag()->add('success', 'Donation Reassigned Successfully');
                  return $this->redirectToRoute('donation_show', array('campaignUrl'=>$campaign->getUrl(), 'id'=>$donation->getId()));
              }
            }else{
              $this->get('session')->getFlashBag()->add('warning', 'Classroom is required');
              $failure = true;
            }

          }elseif($type == 'student' && empty($params['setClassroomFlag'])){
            if(null !== $params['donation']['studentID']){
              $student = $em->getRepository('AppBundle:Student')->find($params['donation']['studentID']);
              if(is_null($student)){
                $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this student.');
              }else{
                  $donation->setType("student");
                  $donation->setStudent($student);
                  $donation->setClassroom($student->getClassroom());
                  $donation->setTransactionId(strtoupper(md5(uniqid(rand(), true))));
                  $donation->setStudentConfirmedFlag(true);

                  $em->persist($donation);
                  $em->flush();

                  $this->get('session')->getFlashBag()->add('success', 'Donation Reassigned Successfully');
                  return $this->redirectToRoute('donation_show', array('campaignUrl'=>$campaign->getUrl(), 'id'=>$donation->getId()));
              }
            }else{
              $this->get('session')->getFlashBag()->add('warning', 'Student is required');
            }

          }

      }

      return $this->render('donation/donation.form.html.twig', array(
          'donation' => $donation,
          'campaign' => $campaign,
          'teams' => $em->getRepository('AppBundle:Team')->findBy(array('campaign' => $campaign)),
          'type' => $type,
          'classrooms' => $em->getRepository('AppBundle:Classroom')->findBy(array('campaign' => $campaign), array('grade' => 'asc', 'name'=>'asc'))
      ));

    }











    /**
     * Finds and displays a Donation entity.
     *
     * @Route("/show/{id}", name="donation_show")
     * @Method("GET")
     */
    public function showAction(Donation $donation, $campaignUrl)
    {
      $logger = $this->get('logger');
      $em = $this->getDoctrine()->getManager();

      //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
      $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
      if(is_null($campaign)){
        $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this campaign.');
        return $this->redirectToRoute('homepage');
      }

      //CODE TO CHECK TO SEE IF USER HAS PERMISSIONS TO CAMPAIGN
      $campaignHelper = new CampaignHelper($em, $logger);
      if(!$campaignHelper->campaignPermissionsCheck($this->get('security.token_storage')->getToken()->getUser(), $campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'You do not have permissions to this campaign.');
          return $this->redirectToRoute('homepage');
      }


        return $this->render('donation/donation.show.html.twig', array(
            'donation' => $donation,
            'campaign' => $campaign
        ));
    }

    /**
     * Displays a form to edit an existing Donation entity.
     *
     * @Route("/edit/{donationID}", name="donation_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, $donationID)
    {

      $logger = $this->get('logger');
      $this->denyAccessUnlessGranted('ROLE_USER');

      $em = $this->getDoctrine()->getManager();

      //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
      $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
      if(is_null($campaign)){
        $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this campaign.');
        return $this->redirectToRoute('homepage');
      }

      //CODE TO CHECK TO SEE IF USER HAS PERMISSIONS TO CAMPAIGN
      $campaignHelper = new CampaignHelper($em, $logger);
      if(!$campaignHelper->campaignPermissionsCheck($this->get('security.token_storage')->getToken()->getUser(), $campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'You do not have permissions to this campaign.');
          return $this->redirectToRoute('homepage');
      }

      //CODE TO CHECK TO SEE IF DONATION EXISTS
      $donation = $em->getRepository('AppBundle:Donation')->findOneBy(array('id'=>$donationID, 'campaign' => $campaign));
      if(is_null($donation)){
        $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this donation.');
        return $this->redirectToRoute('donation_index', array('campaignUrl'=>$campaign->getUrl()));
      }


      if ($request->isMethod('POST')) {
          $params = $request->request->all();
      }

        return $this->render('donation/donation.form.html.twig', array(
            'donation' => $donation,
            'campaign' => $campaign
        ));
    }

    /**
     * Deletes a Donation entity.
     *
     * @Route("/delete/{id}", name="donation_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Donation $donation)
    {
        $entity = 'Donation';
        $form = $this->createDeleteForm($donation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($donation);
            $em->flush();
        }

        return $this->redirectToRoute('donation_index');
    }

    /**
     * Creates a form to delete a Donation entity.
     *
     * @param Donation $donation The Donation entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Donation $donation)
    {
        $entity = 'Donation';

        return $this->createFormBuilder()
            ->setAction($this->generateUrl('donation_delete', array('id' => $donation->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }






    /**
     * Displays a form to edit an existing Team entity.
     *
     * @Route("/show/{donationID}/verify_student/", name="donation_student_verify")
     * @Method({"GET", "POST"})
     */
    public function verifyStudentAction(Request $request, $campaignUrl, $donationID)
    {
        $logger = $this->get('logger');
        $this->denyAccessUnlessGranted('ROLE_USER');

        $em = $this->getDoctrine()->getManager();

        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
        if(is_null($campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this campaign.');
          return $this->redirectToRoute('homepage');
        }

        //CODE TO CHECK TO SEE IF USER HAS PERMISSIONS TO CAMPAIGN
        $campaignHelper = new CampaignHelper($em, $logger);
        if(!$campaignHelper->campaignPermissionsCheck($this->get('security.token_storage')->getToken()->getUser(), $campaign)){
            $this->get('session')->getFlashBag()->add('warning', 'You do not have permissions to this campaign.');
            return $this->redirectToRoute('homepage');
        }

        //CODE TO CHECK TO SEE IF DONATION EXISTS
        $donation = $em->getRepository('AppBundle:Donation')->findOneBy(array('id'=>$donationID, 'campaign' => $campaign));
        if(is_null($donation)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this donation.');
          return $this->redirectToRoute('donation_index', array('campaignUrl'=>$campaign->getUrl()));
        }


        if(null !== $request->query->get('action') && null !== $request->query->get('studentID')){
            $failure = false;
            $studentID = $request->query->get('studentID');
            $logger->debug("Linking Donation #".$donation->getId()." with student ".$studentID);

            $student = $em->getRepository('AppBundle:Student')->find($studentID);
            if(is_null($student)){
              $logger->debug("Could not find Student");
              $this->get('session')->getFlashBag()->add('warning', 'We are sorry, There was an issue adding that student.');
              $failure = true;
            }

            if(!$failure){
              $donation->setStudent($student);
              $donation->setStudentConfirmedFlag(true);
              $em->persist($donation);
              $em->flush();
              return $this->redirectToRoute('donation_show', array('campaignUrl'=>$campaign->getUrl(), 'id'=>$donation->getId()));
            }
        }

        return $this->render('donation/donation.verify.html.twig', array(
            'students' => $donation->getClassroom()->getStudents(),
            'classroom' => $donation->getClassroom(),
            'donation' => $donation,
            'campaign' => $campaign,
        ));
    }



    /**
     * Upload and Validate Donation File.
     *
     * @Route("/upload", name="donation_upload")
     * @Method({"GET", "POST"})
     */

    public function uploadForm(Request $request, $campaignUrl)
    {
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();


        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
        if(is_null($campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this campaign.');
          return $this->redirectToRoute('homepage');
        }

        //CODE TO CHECK TO SEE IF USER HAS PERMISSIONS TO CAMPAIGN
        $campaignHelper = new CampaignHelper($em, $logger);
        if(!$campaignHelper->campaignPermissionsCheck($this->get('security.token_storage')->getToken()->getUser(), $campaign)){
            $this->get('session')->getFlashBag()->add('warning', 'You do not have permissions to this campaign.');
            return $this->redirectToRoute('homepage');
        }


        if ($request->isMethod('POST')) {
            $fail = false;
            $params = $request->request->all();
            $logger->debug("Date Provided:".$params['donation']['date']);
            $uploadDate = DateTime::createFromFormat('Y-m-d', $params['donation']['date']);
            $uploadDateString = $params['donation']['date'];

            //This transactionID is used so we can ensure we are loading the correct file "later"
            //Seperator = '_' (Underscore)
            //The File Name Scheme is transactionID_campaignID_UploadDate_validation-state.csv
            $transactionID = strtoupper(md5(uniqid(rand(), true)));
            $file = $request->files->get('donation');
            $logger->debug("Uploaded File Name: ".$file['file']->getClientOriginalName());
            $logger->debug("Uploaded File Extension: ".$file['file']->getClientOriginalExtension());

            if ($file['file']->getClientOriginalExtension() == 'csv') {
                $fileName = $transactionID.'_'.$campaign->getId().'_'.$uploadDateString.'_uploaded.csv';
                $file['file']->move(
                    $this->getParameter('protected_upload_directory'),
                    $fileName
                );

                $logger->Debug('File was a .csv, attempting to load');

                $fileIndexOffset = 0;
                $templateFields = array('student_id', 'classroom_id', 'classroom', 'student_name', 'donation_amount');

                $CSVHelper = new CSVHelper();
                $CSVHelper->setHeaderRowIndex($fileIndexOffset);

                $CSVHelper->processFile($this->getParameter('protected_upload_directory').'/', $fileName);

                $CSVHelper->cleanAmounts();

                $donationSummary = [];
                $donationSummary['donation_amount'] = 0;
                $donationSummary['donations'] = 0;
                $fileFailed = false;

                if ($CSVHelper->validateHeaders($templateFields)) {
                    $errorMessages = [];
                    $errorMessage;

                    foreach ($CSVHelper->getData() as $i => $item) {
                        $rowFailure = false;
                        unset($studentID);
                        unset($errorMessage);
                        unset($student);
                        unset($studentIDAlt);
                        unset($grade);
                        unset($classroom);
                        $teamPageFlag = false;

                        $student = $em->getRepository('AppBundle:Student')->find($item['student_id']);
                        if (empty($student)) {
                            $rowFailure = true;
                            $fileFailed = true;
                            $errorMessage = new ValidationHelper(array(
                            'entity' => 'Donation',
                            'row_index' => ($i + 2 + $fileIndexOffset),
                            'error_field' => 'student_id',
                            'error_field_value' => $item['student_id'],
                            'error_message' => 'Could not find student',
                            'error_level' => ValidationHelper::$level_error, ));
                        }

                        if (is_null($item['donation_amount']) || !isset($item['donation_amount']) || empty($item['donation_amount']) || strcmp($item['donation_amount'], '') == 0) {
                          $rowFailure = true;
                        }else{
                          $donationSummary['donation_amount'] += intval($item['donation_amount']);
                          $donationSummary['donations'] ++;
                        }

                        if (!$rowFailure) {
                            $donation = $this->getDoctrine()->getRepository('AppBundle:Donation')->findOneBy(
                            array('student' => $student, 'donatedAt' => $uploadDate, 'type' => 'student', 'paymentMethod'=>'cash'));

                            //Going to perform "Insert" vs "Update"
                            if (!empty($donation)) {
                                $rowFailure = true;
                                $fileFailed = true;
                                $errorMessage = new ValidationHelper(array(
                                  'entity' => 'Donation',
                                  'row_index' => ($i + 2 + $fileIndexOffset),
                                  'error_field' => 'N/A',
                                  'error_field_value' => 'N/A',
                                  'error_message' => 'A donation for this student and date already exists #'.$donation->getId(),
                                  'error_level' => ValidationHelper::$level_error, ));
                            }
                        }

                        //Push Error Message
                        if (isset($errorMessage)) {
                            array_push($errorMessages, $errorMessage->getMap());
                        }
                    }

                    if(!$fileFailed){
                      $newfileName = $transactionID.'_'.$campaign->getId().'_'.$uploadDateString.'_validated.csv';
                      rename ($this->getParameter('protected_upload_directory').'/'.$fileName, $this->getParameter('protected_upload_directory').'/'.$newfileName);
                      $fileName = $newfileName;
                    }

                    return $this->render('donation/donation.validate.html.twig', array(
                      'error_messages' => $errorMessages,
                      'campaign' => $campaign,
                      'transactionID' => $fileName,
                      'donation_summary' => $donationSummary
                    ));

                } else {
                    $logger->info('file does not have mandatory fields. ['.implode(', ', $templateFields).']. Please validate it was downloaded from the "FUNRUN LEDGER"');
                    $this->addFlash(
                        'danger',
                        'file does not have mandatory fields. ['.implode(', ', $templateFields).']. Please validate you are matching the Cash Ledger file format'
                    );
                }
            } else {
                $logger->info('File was not a .csv');
                $this->addFlash(
                    'danger',
                    'File was not a .csv'
                );
            }
        }

        return $this->render('donation/donation.upload.form.html.twig', array(
          'campaign' => $campaign,
      ));
    }


    /**
     * Actually Load Donation File
     *
     * @Route("/load", name="donation_load")
     * @Method({"GET", "POST"})
     */

    public function fileLoadAction(Request $request, $campaignUrl)
    {
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();


        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
        if(is_null($campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this campaign.');
          return $this->redirectToRoute('homepage');
        }

        //CODE TO CHECK TO SEE IF USER HAS PERMISSIONS TO CAMPAIGN
        $campaignHelper = new CampaignHelper($em, $logger);
        if(!$campaignHelper->campaignPermissionsCheck($this->get('security.token_storage')->getToken()->getUser(), $campaign)){
            $this->get('session')->getFlashBag()->add('warning', 'You do not have permissions to this campaign.');
            return $this->redirectToRoute('homepage');
        }

        if(null !== $request->query->get('transactionID')){
          $failure = false;
          $fileName = $request->query->get('transactionID');
          $fileNameParts = explode('_', $fileName);
          $uploadDateString = $fileNameParts[2];
          $uploadDate = DateTime::createFromFormat('Y-m-d', $uploadDateString);

          $fileCampaignID = $fileNameParts[1];
          $fileValidationFlag = $fileNameParts[3];

          if(intval($fileCampaignID) !== $campaign->getId()){
            $logger->info("Campaign ID #".$fileCampaignID.' does not match this campaign #'.$campaign->getId());
            $this->get('session')->getFlashBag()->add('danger', 'Donation file is not for this campaign');
            return $this->redirectToRoute('donation_upload', array('campaignUrl'=>$campaign->getUrl()));
          }

          if($fileValidationFlag !== "validated.csv"){
            $logger->info("User tried to validate an invalid file");
            $this->get('session')->getFlashBag()->add('danger', 'Donation file was not validated');
            return $this->redirectToRoute('donation_upload', array('campaignUrl'=>$campaign->getUrl()));
          }

          if(!file_exists($this->getParameter('protected_upload_directory').'/'.$fileName)){
            $logger->info("Could not upload ".$fileName);
            $this->get('session')->getFlashBag()->add('danger', 'Could not find file');
            return $this->redirectToRoute('donation_upload', array('campaignUrl'=>$campaign->getUrl()));
          }


          $CSVHelper = new CSVHelper();
          $fileIndexOffset = 0;
          $CSVHelper->setHeaderRowIndex($fileIndexOffset);

          $CSVHelper->processFile($this->getParameter('protected_upload_directory').'/', $fileName);

          $CSVHelper->cleanAmounts();

          foreach ($CSVHelper->getData() as $i => $item) {
              if (is_null($item['donation_amount']) || !isset($item['donation_amount']) || empty($item['donation_amount']) || strcmp($item['donation_amount'], '') == 0) {
                null;
              }else{
                $donation = new Donation();
                $student = $em->getRepository('AppBundle:Student')->find($item['student_id']);

                $donation->setStudent($student);
                $donation->setClassroom($student->getClassroom());
                $donation->setCampaign($campaign);
                $donation->setAmount($item['donation_amount']);
                $donation->setTransactionId(strtoupper(md5(uniqid(rand(), true))));
                $donation->setCreatedBy($this->get('security.token_storage')->getToken()->getUser());
                $donation->setStudentConfirmedFlag(true);
                $donation->setType("student");
                $donation->setDonatedAt($uploadDate);
                $donation->setPaymentMethod("cash");
                $donation->setDonationStatus("ACCEPTED");
                $em->persist($donation);

              }
          }
          $em->flush();
          $CSVHelper->unlink();
          $this->get('session')->getFlashBag()->add('success', 'Donation File has been uploaded');
          return $this->redirectToRoute('donation_index', array('campaignUrl'=>$campaign->getUrl()));

        }
  }
}
