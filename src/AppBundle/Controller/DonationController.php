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

                  $donationHelper = new DonationHelper($em, $logger);
                  $donationHelper->reloadDonationDatabase(array('campaign'=>$campaign));

                  $em->persist($donation);
                  $em->flush();
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
              $donationHelper->reloadDonationDatabase(array('campaign'=>$campaign));

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
                  $donationHelper->reloadDonationDatabase(array('campaign'=>$campaign));

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
                  $donationHelper->reloadDonationDatabase(array('campaign'=>$campaign));

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
        return $this->redirectToRoute('donation_index', array('campaignUrl'=>$campaign.url));
      }


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



      if($type == 'campaign'){
        $donation->setType("campaign");

        $em->persist($donation);
        $em->flush();

        $donationHelper = new DonationHelper($em, $logger);
        $donationHelper->reloadDonationDatabase(array('campaign'=>$campaign));

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

                  $donationHelper = new DonationHelper($em, $logger);
                  $donationHelper->reloadDonationDatabase(array('campaign'=>$campaign));

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

                  $donationHelper = new DonationHelper($em, $logger);
                  $donationHelper->reloadDonationDatabase(array('campaign'=>$campaign));

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

                  $donationHelper = new DonationHelper($em, $logger);
                  $donationHelper->reloadDonationDatabase(array('campaign'=>$campaign));

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

        return $this->redirectToRoute(strtolower($entity).'_index');
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
            ->setAction($this->generateUrl(strtolower($entity).'_delete', array('id' => $donation->getId())))
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
     * Upload multiple Donation via CSV File.
     *
     * @Route("/upload", name="donation_upload")
     * @Method({"GET", "POST"})
     */

    public function uploadForm(Request $request)
    {
        $logger = $this->get('logger');
        $entity = 'Donation';
        $mode = 'update';

        $fileType = $request->query->get('file_type');
        $logger->debug('file_type: '.$fileType);
        if (strcmp($fileType, 'Offlinedonation') !== 0 && strcmp($fileType, 'Causevoxdonation') !== 0) {
            $this->addFlash(
              'warning',
              'File Type '.$fileType.' not found');

            return $this->redirectToRoute(strtolower($entity).'_index');
        }
        $form = $this->createForm('AppBundle\Form\UploadType', array('entity' => $entity, 'file_type' => $fileType, 'role' => $this->getUser()->getRoles()));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null != $form['upload_mode']->getData()) {
                $mode = $form['upload_mode']->getData();
            } else {
                $logger->error('No mode was selected. defaulted to update');
            }

            $uploadFile = $form['attachment']->getData();

            if (strpos($uploadFile->getClientOriginalName(), '.csv') !== false) {
                $logger->info('File was a .csv, attempting to load');
                $uploadFile->move('temp/', strtolower($entity).'.csv');
                //the offline donation file starts at 1, while causevox, starts at 0.....
                $fileIndexOffset = 0;

                if (strcmp($fileType, 'Offlinedonation') == 0) {
                    $templateFields = array('date', 'grade', 'classrooms_name', 'students_name', 'amount');
                    $fileIndexOffset = 1;
                } else {
                    $templateFields = array('donation_page',
                  'fundraiser_first_name',
                  'fundraiser_last_name',
                  'fundraiser_email',
                  'fundraiser_location',
                  'donor_first_name',
                  'donor_last_name',
                  'donor_email',
                  'donor_comment',
                  'anonymous',
                  'line_1',
                  'line_2',
                  'city',
                  'state',
                  'zip_code',
                  'country',
                  'amount',
                  'est_cc_fee',
                  'causevox_fee',
                  'tip',
                  'type',
                  'recurring',
                  'subscribed',
                  'giftaid',
                  'transaction_id',
                  'donated_at',
                  'classrooms_name',
                  'students_name', );
                }

                $CSVHelper = new CSVHelper();
                $CSVHelper->setHeaderRowIndex($fileIndexOffset);
                $CSVHelper->processFile('temp/', strtolower($entity).'.csv');

                if (strcmp($fileType, 'Causevoxdonation') == 0) {
                    $CSVHelper->getGradefromClassroomName();
                    $CSVHelper->cleanClassroomNames();
                    $CSVHelper->getFirstNameFromFullName();
                }

                $CSVHelper->cleanAmounts();

                if ($CSVHelper->validateHeaders($templateFields)) {
                    $em = $this->getDoctrine()->getManager();

                    if (strcmp($mode, 'truncate') == 0) {
                        $logger->info('User selected to [truncate] table');

                        $qb = $em->createQueryBuilder();

                        if (strcmp($fileType, 'Offlinedonation') == 0) {
                            $qb->delete('AppBundle:'.$entity, 's');
                            $qb->where("s.source = 'Offlinedonation'");
                            $query = $qb->getQuery();
                            $query->getResult();
                            $em->flush();

                            $this->addFlash(
                              'info',
                              'The Manual donations have been deleted'
                          );
                        } elseif (strcmp($fileType, 'Causevoxdonation') == 0) {
                            $qb->delete('AppBundle:'.$entity, 's');
                            $qb->where("s.source = 'Causevoxdonation'");
                            $query = $qb->getQuery();
                            $query->getResult();
                            $em->flush();

                            $this->addFlash(
                              'info',
                              'Existing Causevox donations were deleted'
                          );
                        }
                    }

                    $logger->info('Uploading Data');
                    $em = $this->getDoctrine()->getManager();
                    $errorMessages = [];
                    $errorMessage;

                    foreach ($CSVHelper->getData() as $i => $item) {
                        $failure = false;
                        unset($studentID);
                        unset($errorMessage);
                        unset($student);
                        unset($studentIDAlt);
                        unset($grade);
                        unset($classroom);
                        $teamPageFlag = false;

                        if (strcmp($fileType, 'Causevoxdonation') == 0 && isset($item['donation_page']) && !strcmp($item['donation_page'], 'none') == 0) {
                            $urlString = substr($item['donation_page'], 0, 5);
                            if (strcmp($urlString, '/team') == 0) {
                                $teamPageFlag = true;
                                $urlString = substr($item['donation_page'], 6, strlen($item['donation_page'])); // Chopping off the '/team'
                            $queryString = sprintf("SELECT IDENTITY(u.classroom, 'id') as classroom_id FROM AppBundle:Causevoxteam u WHERE u.url = '%s'", $urlString);
                                $logger->debug('QueryString: '.$queryString);
                                $result = $em->createQuery($queryString)->getResult();
                                if (!empty($result)) {
                                    $classroomID = $result[0]['classroom_id'];
                                    $logger->debug('Row ['.($i + 2 + $fileIndexOffset).'] - Found classroom [#'.$classroomID.'] using associated Causevoxteam URL "'.$item['donation_page'].'"');
                                } else {
                                    $failure = true;
                                    $errorMessage = new ValidationHelper(array(
                                'entity' => $entity,
                                'row_index' => ($i + 2 + $fileIndexOffset),
                                'error_field' => 'donation_page',
                                'error_field_value' => $item['donation_page'],
                                'error_message' => 'Donation made to team page, but we could not find the associated team page',
                                'error_level' => ValidationHelper::$level_error, ));
                                }
                            } else {
                                //GETTING URL STRING TO FIND FROM TABLE
                            $urlString = substr($item['donation_page'], 1, strlen($item['donation_page'])); // Chopping off the '/'
                            //$lastinitial = substr($lastname,0,1).'.';
                            $queryString = sprintf("SELECT IDENTITY(u.student, 'id') as student_id FROM AppBundle:Causevoxfundraiser u WHERE u.url = '%s'", $urlString);
                                $logger->debug('QueryString: '.$queryString);
                                $result = $em->createQuery($queryString)->getResult();
                                if (!empty($result)) {
                                    $studentID = $result[0]['student_id'];
                                    $logger->debug('Row ['.($i + 2 + $fileIndexOffset).'] - Found student "'.$item['students_name'].'" [#'.$studentID.'] using associated Causevoxfundraiser URL "'.$item['donation_page'].'"');
                                }
                            }
                        }

                        if (!$failure) {
                            if (strcmp($fileType, 'Causevoxdonation') == 0 && strcmp($item['type'], 'manual') == 0) {
                                $failure = true;
                              //We do not process "manual" causevox donations as they are offline donations we collect elsewhere
                            } elseif (strcmp($fileType, 'Offlinedonation') == 0) {
                                $item['type'] = 'manual';
                            }
                        }

                        if (!$failure) {
                            if (is_null($item['amount']) || !isset($item['amount']) || empty($item['amount']) || strcmp($item['amount'], '') == 0) {
                                $failure = true;
                              //We do not notify if amount is empty.....we just ignore it.
                            }
                        }

                        if (!$failure) {
                            if (strcmp($fileType, 'Causevoxdonation') == 0) {
                                if (!isset($item['donated_at']) || empty($item['donated_at']) || strcmp('none', $item['donated_at']) == 0) {
                                    $failure = true;
                                    $errorMessage = new ValidationHelper(array(
                                  'entity' => $entity,
                                  'row_index' => ($i + 2 + $fileIndexOffset),
                                  'error_field' => 'date',
                                  'error_field_value' => $item['date'],
                                  'error_message' => 'Date cannot be null',
                                  'error_level' => ValidationHelper::$level_error, ));
                                }
                            } else {
                                if (!isset($item['date']) || empty($item['date']) || strcmp('none', $item['date']) == 0) {
                                    $failure = true;
                                    $errorMessage = new ValidationHelper(array(
                                  'entity' => $entity,
                                  'row_index' => ($i + 2 + $fileIndexOffset),
                                  'error_field' => 'date',
                                  'error_field_value' => $item['date'],
                                  'error_message' => 'Date cannot be null',
                                  'error_level' => ValidationHelper::$level_error, ));
                                }
                            }
                        }

                        //Here is our backup/Alt logic
                        if (!$failure) {
                            $grade = $this->getDoctrine()->getRepository('AppBundle:Grade')->findOneByName($item['grade']);

                            if (empty($grade) && !isset($studentID) && !$teamPageFlag) {
                                $failure = true;
                                $errorMessage = new ValidationHelper(array(
                                'entity' => $entity,
                                'row_index' => ($i + 2 + $fileIndexOffset),
                                'error_field' => 'grade',
                                'error_field_value' => $item['grade'],
                                'error_message' => 'Could not find grade',
                                'error_level' => ValidationHelper::$level_error, ));
                            }
                        }

                        if (!$failure && isset($grade)) {
                            $classroom = $this->getDoctrine()->getRepository('AppBundle:Classroom')->findOneByClassroomName($item['classrooms_name']);
                            $queryString = sprintf("SELECT u.id FROM AppBundle:Classroom u WHERE u.classroomName = '%s'", $item['classrooms_name']);
                            $result = $em->createQuery($queryString)->getResult();
                            if (!empty($result)) {
                                $classroomID = $result[0]['id'];
                                $logger->debug('Row ['.($i + 2 + $fileIndexOffset).'] - Found classroom "'.$item['classrooms_name'].'" [#'.$classroomID.'] using name "'.$item['classrooms_name'].'"');
                            } else if(empty($result) && !isset($studentID) && !isset($classroomID) && !$teamPageFlag) {
                                $failure = true;
                                $errorMessage = new ValidationHelper(array(
                                'entity' => $entity,
                                'row_index' => ($i + 2 + $fileIndexOffset),
                                'error_field' => 'classrooms_name',
                                'error_field_value' => $item['classrooms_name'],
                                'error_message' => 'Could not find classroom',
                                'error_level' => ValidationHelper::$level_error, ));
                            }
                        }

                        //Here is our find student logic. We try a lot of different methods to try and find it....
                        if (!$failure && isset($grade) && isset($classroom)) {
                            if (!isset($studentIDAlt)) {
                                $queryString = sprintf("SELECT u.id FROM AppBundle:Student u WHERE u.classroom = '%s' AND u.name = '%s'", $classroomID, $item['students_name']);
                                $result = $em->createQuery($queryString)->getResult();

                                if (!empty($result)) {
                                    $studentIDAlt = $result[0]['id'];
                                    $logger->debug('Row ['.($i + 2 + $fileIndexOffset).'] - Found student "'.$item['students_name'].'" [#'.$studentIDAlt.'] using provided name');
                                }
                            }

                            if (!isset($studentIDAlt)) {
                                $queryString = sprintf("SELECT u.id FROM AppBundle:Student u WHERE u.classroom = '%s' AND u.name = '%s'", $classroomID, $item['students_first_name']);
                                $result = $em->createQuery($queryString)->getResult();
                                if (!empty($result)) {
                                    $studentIDAlt = $result[0]['id'];
                                    $logger->debug('Row ['.($i + 2 + $fileIndexOffset).'] - Found student "'.$item['students_name'].'" [#'.$studentIDAlt.'] using first name fuzzy match "'.$item['students_first_name'].'"');
                                }
                            }

                            if (!isset($studentIDAlt)) {
                                $queryString = sprintf("SELECT u.id FROM AppBundle:Student u WHERE u.classroom = '%s' AND u.name = '%s'", $classroomID, $item['students_name_with_initial']);
                                $result = $em->createQuery($queryString)->getResult();

                                if (!empty($result)) {
                                    $studentIDAlt = $result[0]['id'];
                                    $logger->debug('Row ['.($i + 2 + $fileIndexOffset).'] - Found student "'.$item['students_name'].'" [#'.$studentIDAlt.'] using first name + last initial fuzzy match "'.$item['students_name_with_initial'].'"');
                                }
                            }

                          if(isset($studentIDAlt)){
                            $studentID = $studentIDAlt;
                          }

                          //If it is not a team page and we didn't find a student, it is a failure
                          if (!isset($studentID) && !$teamPageFlag) {
                              $failure = true;
                              $errorMessage = new ValidationHelper(array(
                                  'entity' => $entity,
                                  'row_index' => ($i + 2 + $fileIndexOffset),
                                  'error_field' => 'students_name, classroom, grade',
                                  'error_field_value' => $item['students_name'].', '.$item['classrooms_name'].', '.$item['grade'],
                                  'error_message' => 'Could not find student',
                                  'error_level' => ValidationHelper::$level_error, ));
                          }
                        } //END STUDENT FIND LOGIC

                        if (!$failure) {
                            if ($teamPageFlag) {
                                $classroom = $em->find('AppBundle:Classroom', $classroomID);
                                if(isset($studentID)){
                                   $student = $em->find('AppBundle:Student', $studentID);
                                }
                            } else {
                                $student = $em->find('AppBundle:Student', $studentID);
                                $classroom = $em->find('AppBundle:Classroom', $student->getClassroom());
                            }

                          //Example: 2016-08-25 16:35:54
                          //Causevox donations are given to us as UTC...which we need to convert back to EST
                          if (strcmp($fileType, 'Causevoxdonation') == 0) {
                              $tempDate = new DateTime($item['donated_at'],  new DateTimeZone('UTC'));
                              $tempDate->setTimezone(new DateTimeZone('America/New_York'));
                              $dateString = $tempDate->format('Y-m-d').' 00:00:00';
                              $date = DateTime::createFromFormat('Y-m-d H:i:s', $dateString,  new DateTimeZone('America/New_York'));
                          } else {
                              $tempDate = new DateTime($item['date']);
                              $dateString = $tempDate->format('Y-m-d').' 00:00:00';
                              $date = DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
                          }

                            if (strcmp($fileType, 'Causevoxdonation') == 0) {
                              if(!$teamPageFlag){
                                $donation = $this->getDoctrine()->getRepository('AppBundle:'.$entity)->findOneBy(
                                array('student' => $student, 'donatedAt' => $date, 'transactionId' => $item['transaction_id'], 'source' => $fileType));
                              }else{
                                $donation = $this->getDoctrine()->getRepository('AppBundle:'.$entity)->findOneBy(
                                array('donationPage' => $item['donation_page'], 'donatedAt' => $date, 'transactionId' => $item['transaction_id'], 'source' => $fileType));
                              }
                            } elseif (strcmp($fileType, 'Offlinedonation') == 0) {
                                $donation = $this->getDoctrine()->getRepository('AppBundle:'.$entity)->findOneBy(
                                array('student' => $student, 'donatedAt' => $date, 'source' => $fileType));
                            }

                          //Going to perform "Insert" vs "Update"
                          if (empty($donation)) {
                              $logger->debug($entity.' not found....creating new record');
                              $donation = new Donation();
                          } else {
                              $logger->debug($entity.' found....updating.');
                              $failure = true;

                              $errorMessage = new ValidationHelper(array(
                                'entity' => $entity,
                                'row_index' => ($i + 2 + $fileIndexOffset),
                                'error_field' => 'N/A',
                                'error_field_value' => 'N/A',
                                'error_message' => 'A donation for this student and date already exists #'.$donation->getId(),
                                'error_level' => ValidationHelper::$level_error, ));


                          }
                        }


                        if (!$failure) {
                            if (strcmp($fileType, 'Causevoxdonation') == 0) {
                                //a lot more information is collected from causevox....
                                $donation->setTip($item['tip']);
                                $donation->setEstimatedCcFee($item['est_cc_fee']);
                                $donation->setCausevoxFee($item['causevox_fee']);

                                $donation->setDonorFirstName($item['donor_first_name']);
                                $donation->setDonorLastName($item['donor_last_name']);
                                $donation->setDonorEmail($item['donor_email']);
                                $donation->setDonorComment($item['donor_comment']);
                                $donation->setDonationPage($item['donation_page']);
                                $donation->setTransactionId($item['transaction_id']);
                            }

                            $donation->setSource($fileType);
                            $donation->setType($item['type']);
                            $donation->setAmount($item['amount']);
                            $donation->setDonatedAt($date);
                            if(isset($student)){
                              $donation->setStudent($student);
                            }
                            $donation->setClassroom($classroom);
                            $validator = $this->get('validator');
                            $errors = $validator->validate($donation);

                            if (strcmp($mode, 'validate') !== 0) {
                                if (count($errors) > 0) {
                                    $errorsString = (string) $errors;
                                    $logger->error('[ROW #'.($i + 2 + $fileIndexOffset).'] Could not add ['.$entity.']: '.$errorsString);
                                    $this->addFlash(
                                        'danger',
                                        '[ROW #'.($i + 2 + $fileIndexOffset).'] Could not add ['.$entity.']: '.$errorsString
                                    );
                                } else {
                                    $em->persist($donation);
                                    $em->flush();
                                    $em->clear();
                                }
                            } //Otherwise we do Nothing....
                        }
                        if (isset($errorMessage) && strcmp($mode, 'validate') !== 0) {
                            $this->addFlash(
                                  $errorMessage->getErrorLevel(),
                                  $errorMessage->printFlashBagMessage()
                              );
                        }

                        //Push Error Message
                        if (isset($errorMessage)) {
                            array_push($errorMessages, $errorMessage->getMap());
                        }
                    }

                    if (strcmp($mode, 'validate') !== 0) {
                        $em->flush();
                        $em->clear();

                        return $this->redirectToRoute(strtolower($entity).'_index');
                    } else {
                        return $this->render('crud/validate.html.twig', array(
                          'error_messages' => $errorMessages,
                          'entity' => $entity,
                          'file_type' => $fileType,
                      ));
                    }
                } else {
                    $logger->info('file does not have mandatory fields. ['.implode(', ', $templateFields).']. Please validate it was downloaded from the "FUNRUN LEDGER"');
                    $logger->info('File was not a .csv');
                    $this->addFlash(
                        'danger',
                        'file does not have mandatory fields. ['.implode(', ', $templateFields).']. Please validate you are matching the '.$fileType.' file format'
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

        return $this->render('crud/upload.html.twig', array(
          'form' => $form->createView(),
          'entity' => $entity,
      ));
    }

}
