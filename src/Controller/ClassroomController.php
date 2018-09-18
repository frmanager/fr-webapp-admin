<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Classroom;
use App\Entity\Grade;
use App\Entity\Student;
use App\Entity\Campaign;
use App\Utils\ValidationHelper;
use App\Utils\CSVHelper;
use App\Utils\CampaignHelper;
use App\Utils\QueryHelper;
use App\Utils\DonationHelper;
use DateTime;

/**
 * Classroom controller.
 *
 * @Route("/{campaignUrl}/classrooms")
 */
class ClassroomController extends Controller
{
  /**
   * Lists all Classroom entities.
   *
   * @Route("/", name="classroom_index")
   * @Method({"GET", "POST"})
   */
  public function classroomIndexAction($campaignUrl, LoggerInterface $logger)
  {
     
      $entity = 'Classroom';
      $em = $this->getDoctrine()->getManager();

      //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
      $campaign = $em->getRepository('App:Campaign')->findOneByUrl($campaignUrl);
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

      $queryHelper = new QueryHelper($em, $logger);
      $tempDate = new DateTime();
      $dateString = $tempDate->format('Y-m-d').' 00:00:00';
      $reportDate = DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
      // replace this example code with whatever you need
      return $this->render('classroom/classroom.index.html.twig', array(
        'classrooms' => $queryHelper->getClassroomRanks(array('campaign' => $campaign, 'limit'=> 0)),
        'entity' => strtolower($entity),
        'campaign' => $campaign,
      ));

  }

    /**
     * Creates a new Classroom entity.
     *
     * @Route("/new", name="classroom_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, $campaignUrl, LoggerInterface $logger)
    {
        $entity = 'Classroom';
       
        $em = $this->getDoctrine()->getManager();

        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('App:Campaign')->findOneByUrl($campaignUrl);
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

        $classroom = new Classroom();


        if ($request->isMethod('POST')) {
            $params = $request->request->all();
            $fail = false;

            if(!$fail && empty($params['classroom']['name'])){
              $this->addFlash('warning','Classroom name is required');
              $fail = true;
            }

            if(!$fail && empty($params['classroom']['gradeId'])){
              $this->addFlash('warning','Grade is required');
              $fail = true;
            }else{
              $grade = $em->getRepository('App:Grade')->findOneBy(array('id'=>$params['classroom']['gradeId'], 'campaign' => $campaign));
              if(is_null($grade)){
                $this->addFlash('warning','No Valid Grade was selected');
                $fail = true;
              }
            }

            if(!$fail && empty($params['classroom']['teacherName'])){
              $this->addFlash('warning','Teachers name is required');
              $fail = true;
            }

            if(!$fail){

              $classroom->setName($params['classroom']['name']);
              $classroom->setGrade($grade);
              $classroom->setTeacherName($params['classroom']['teacherName']);

              if(!empty($params['classroom']['email'])){
                $classroom->setEmail($params['classroom']['email']);
              }

              $classroom->setCreatedBy($this->get('security.token_storage')->getToken()->getUser());
              $classroom->setCampaign($campaign);

              $em->persist($classroom);
              $em->flush();
              return $this->redirectToRoute('classroom_index', array('campaignUrl'=> $campaignUrl));

            }

        }

        return $this->render('classroom/classroom.form.html.twig', array(
            'classroom' => $classroom,
            'grades' => $em->getRepository('App:Grade')->findBy(array("campaign"=>$campaign)),
            'campaign' => $campaign,
        ));
    }


    /**
     * Creates a new Classroom entity.
     *
     * @Route("/{classroomID}/add_students", name="classroom_students_new")
     * @Method({"GET", "POST"})
     */
    public function newStudentsAction(Request $request, $campaignUrl, $classroomID, LoggerInterface $logger)
    {
        $entity = 'Classroom';
       
        $em = $this->getDoctrine()->getManager();

        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('App:Campaign')->findOneByUrl($campaignUrl);
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

        //CODE TO CHECK TO SEE IF CLASSROOM EXISTS
        $classroom = $em->getRepository('App:Classroom')->find($classroomID);
        if(is_null($classroom)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this classroom.');
          return $this->redirectToRoute('homepage');
        }


        if ($request->isMethod('POST')) {
            $params = $request->request->all();
            $failure = false;

            foreach($params['classroom']['students'] as $key => $newStudent){
              if(!$failure && !empty($newStudent['name'])){
                $student = new Student();
                $studentCheck = $em->getRepository('App:Student')->findOneBy(array('campaign'=>$campaign, 'classroom' => $classroom, 'name' => $newStudent['name']));

                if(!is_null($studentCheck)){
                  $this->get('session')->getFlashBag()->add('warning', 'A student with the name '.$newStudent['name'].' already exists');
                  $failure = true;
                }
                if(!$failure){
                  $student->setClassroom($classroom);
                  $student->setName($newStudent['name']);
                  $student->setGrade($classroom->getGrade());
                  $student->setCampaign($campaign);
                  $em->persist($student);
                }
              }
            }

            if(!$failure){
              $em->flush();
              $this->get('session')->getFlashBag()->add('success', 'Successfully added students!');
              return $this->redirectToRoute('classroom_show', array('campaignUrl'=> $campaignUrl, 'classroomID' => $classroom->getId()));
            }else{
              return $this->render('classroom/classroom.students.form.html.twig', array(
                  'students' => $params['classroom']['students'],
                  'classroom' => $classroom,
                  'grades' => $em->getRepository('App:Grade')->findBy(array("campaign"=>$campaign)),
                  'campaign' => $campaign,
              ));
            }
        }

        return $this->render('classroom/classroom.students.form.html.twig', array(
            'classroom' => $classroom,
            'grades' => $em->getRepository('App:Grade')->findBy(array("campaign"=>$campaign)),
            'campaign' => $campaign,
        ));
    }



    /**
     * Finds and displays a Classroom entity.
     *
     * @Route("/{classroomID}", name="classroom_show")
     * @Method("GET")
     */
    public function showAction(Request $request, $campaignUrl, $classroomID, LoggerInterface $logger)
    {
       
        $entity = 'Classroom';
        $em = $this->getDoctrine()->getManager();

        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('App:Campaign')->findOneByUrl($campaignUrl);
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

        $classroom = $this->getDoctrine()->getRepository('App:'.strtolower($entity))->findOneById($classroomID);
        //CODE TO CHECK TO SEE IF CLASSROOM EXISTS
        $classroom = $em->getRepository('App:Classroom')->find($classroomID);
        if(is_null($classroom)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this classroom.');
          return $this->redirectToRoute('campaign_index', array('campaignUrl' => $campaignUrl));
        }


        if(null !== $request->query->get('action')){
            $action = $request->query->get('action');

            if($action === 'delete_student'){
              $logger->debug("Performing delete_student");
              if(null == $request->query->get('studentID')){
                $this->get('session')->getFlashBag()->add('warning', 'Could not delete student, ID not provided');
                return $this->redirectToRoute('classroom_show', array('campaignUrl' => $campaign->getUrl(), 'classroomID' => $classroom->getId()));
              }

              $student = $em->getRepository('App:Student')->find($request->query->get('studentID'));
              if(empty($student)){
                $this->get('session')->getFlashBag()->add('warning', 'Could not find student to delete');
                return $this->redirectToRoute('classroom_show', array('campaignUrl' => $campaign->getUrl(), 'classroom_ID' => $classroomID));
              }

              //we don't delete the student, only remove the reference.
              foreach($student->getTeamStudents() as $teamStudent){
                $logger->debug("Updating TeamStudent #".$teamStudent->getId());
                $teamStudent->setStudent(null);
                $teamStudent->setConfirmedFlag(false);
                $em->flush();
              }

              $student = $em->getRepository('App:Student')->find($request->query->get('studentID'));
              $logger->debug("Removing Student #".$student->getId());
              $em->remove($student);
              $logger->debug("Flushing");
              $em->flush();

              $this->get('session')->getFlashBag()->add('info', 'Student has been removed');
              return $this->redirectToRoute('classroom_show', array('campaignUrl' => $campaign->getUrl(), 'classroomID' => $classroomID));
            }
        }


        $qb = $em->createQueryBuilder()->select('u')
               ->from('App:Campaignaward', 'u')
               ->andWhere('u.campaign = :campaignID')
               ->setParameter('campaignID', $campaign->getId())
               ->orderBy('u.amount', 'DESC');

        $campaignAwards = $qb->getQuery()->getResult();

        $queryHelper = new QueryHelper($em, $logger);

        return $this->render('classroom/classroom.show.html.twig', array(
            'classroom' => $classroom,
            'donations' => $queryHelper->getClassroomsData(array('campaign' => $campaign, 'id' => $classroom->getId(), 'limit' => 0)),
            'classroom_rank' => $queryHelper->getClassroomRank($classroom->getId(),array('campaign' => $campaign, 'limit' => 0)),
            'campaign_awards' => $campaignAwards,
            'campaign' => $campaign,
        ));
    }

    /**
     * Displays a form to edit an existing Classroom entity.
     *
     * @Route("/edit/{classroomID}", name="classroom_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, $campaignUrl, $classroomID, LoggerInterface $logger)
    {
       
        $em = $this->getDoctrine()->getManager();

        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('App:Campaign')->findOneByUrl($campaignUrl);
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

        //CODE TO CHECK TO SEE IF CLASSROOM EXISTS
        $classroom = $em->getRepository('App:Classroom')->find($classroomID);
        if(is_null($classroom)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this classroom.');
          return $this->redirectToRoute('homepage');
        }

        if ($request->isMethod('POST')) {
            $params = $request->request->all();
            $fail = false;

            if(!$fail && empty($params['classroom']['name'])){
              $this->addFlash('warning','Classroom name is required');
              $fail = true;
            }

            if(!$fail && empty($params['classroom']['gradeId'])){
              $this->addFlash('warning','Grade is required');
              $fail = true;
            }else{
              $grade = $em->getRepository('App:Grade')->findOneBy(array('id'=>$params['classroom']['gradeId'], 'campaign' => $campaign));
              if(is_null($grade)){
                $this->addFlash('warning','No Valid Grade was selected');
                $fail = true;
              }
            }

            if(!$fail && empty($params['classroom']['teacherName'])){
              $this->addFlash('warning','Teachers name is required');
              $fail = true;
            }

            if(!$fail){

              $classroom->setName($params['classroom']['name']);
              $classroom->setGrade($grade);
              $classroom->setTeacherName($params['classroom']['teacherName']);

              if(!empty($params['classroom']['email'])){
                $classroom->setEmail($params['classroom']['email']);
              }

              $classroom->setCreatedBy($this->get('security.token_storage')->getToken()->getUser());
              $classroom->setCampaign($campaign);

              $em->persist($classroom);
              $em->flush();
              return $this->redirectToRoute('classroom_index', array('campaignUrl'=> $campaignUrl));

            }

        }

        return $this->render('classroom/classroom.form.html.twig', array(
            'classroom' => $classroom,
            'grades' => $em->getRepository('App:Grade')->findBy(array("campaign"=>$campaign)),
            'campaign' => $campaign,
        ));
    }

    /**
     * Deletes a Classroom entity.
     *
     * @Route("/delete/{id}", name="classroom_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Classroom $classroom, $campaignUrl, LoggerInterface $logger)
    {
        $entity = 'Classroom';
       
        $em = $this->getDoctrine()->getManager();

        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('App:Campaign')->findOneByUrl($campaignUrl);
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

        $form = $this->createDeleteForm($classroom);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($classroom);
            $em->flush();
        }

        return $this->redirectToRoute('homepage');
    }

    /**
     * Creates a new Classroom entity.
     *
     * @Route("/upload", name="classroom_upload")
     * @Method({"GET", "POST"})
     */
    public function uploadForm(Request $request)
    {
       
        $entity = 'Classroom';
        $em = $this->getDoctrine()->getManager();

        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('App:Campaign')->findOneByUrl($campaignUrl);
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


        $mode = 'update';
        $form = $this->createForm('App\Form\UploadType', array('entity' => $entity, 'file_type' => $entity, 'role' => $this->getUser()->getRoles()));
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

                $CSVHelper = new CSVHelper();
                $CSVHelper->processFile('temp/', strtolower($entity).'.csv');
                $templateFields = array('classrooms_name', 'grade', 'email');

                if ($CSVHelper->validateHeaders($templateFields)) {
                    $logger->info('Making changes to database');

                    $em = $this->getDoctrine()->getManager();

                    if (strcmp($mode, 'truncate') == 0) {
                        $logger->info('User selected to [truncate] table');

                        $qb = $em->createQueryBuilder();
                        $qb->delete('App:'.$entity, 's');
                        $query = $qb->getQuery();

                        $query->getResult();

                        $em->flush();

                        $this->addFlash(
                            'info',
                            'The Classrooms table has been truncated'
                        );
                    }

                    $logger->info('Uploading Data');
                    $em = $this->getDoctrine()->getManager();
                    $errorMessages = [];
                    $errorMessage;

                    foreach ($CSVHelper->getData() as $i => $item) {
                        $failure = false;
                        unset($errorMessage);
                        $logger->debug('Row ['.$i.'] data: '.print_r($item, true));
                        if (!$failure) {
                            $grade = $this->getDoctrine()->getRepository('App:Grade')->findOneByName($item['grade']);
                            if (empty($grade)) {
                                $failure = true;
                                $errorMessage = new ValidationHelper(array(
                            'entity' => $entity,
                            'row_index' => ($i + 2),
                            'error_field' => 'grade',
                            'error_field_value' => $item['grade'],
                            'error_message' => 'Could not find grade',
                            'error_level' => ValidationHelper::$level_error, ));
                            }
                        }

                        if (!$failure) {
                            $classroom = $this->getDoctrine()->getRepository('App:'.$entity)->findOneBy(
                      array('grade' => $grade->getId(), 'classroomName' => $item['classrooms_name'])
                      );
                      //Going to perform "Insert" vs "Update"
                        if (empty($classroom)) {
                            $logger->debug($entity.' not found....creating new record');
                            $classroom = new Classroom();
                        } else {
                            $logger->debug($entity.' found....updating existing record');
                            if (strcmp($mode, 'truncate') == 0) {
                                //This means there is a duplicate in the file...
                            $failure = true;
                                $errorMessage = new ValidationHelper(array(
                              'entity' => $entity,
                              'row_index' => ($i + 2),
                              'error_field' => 'classrooms_name',
                              'error_field_value' => $item['classrooms_name'],
                              'error_message' => 'Duplicate with '.$entity.' #'.$classroom->getId(),
                              'error_level' => ValidationHelper::$level_warning, ));
                            }
                        }
                            if (!$failure) {
                                $classroom->setClassroomName($item['classrooms_name']);
                                $classroom->setGrade($grade);
                                $classroom->setEmail($item['email']);

                                $validator = $this->get('validator');
                                $errors = $validator->validate($classroom);

                                if (strcmp($mode, 'validate') !== 0) {
                                    if (count($errors) > 0) {
                                        $errorsString = (string) $errors;
                                        $logger->error('[ROW #'.($i + 2).'] Could not add ['.$entity.']: '.$errorsString);
                                        $this->addFlash(
                                    'danger',
                                    '[ROW #'.($i + 2).'] Could not add ['.$entity.']: '.$errorsString
                                );
                                    } else {
                                        $em->persist($classroom);
                                        $em->flush();
                                        $em->clear();
                                    }
                                } //Otherwise we do Nothing....
                            }
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
                      ));
                    }
                } else {
                    $logger->info('file does not have mandatory fields. ['.implode(', ', $templateFields).']');
                    $logger->info('File was not a .csv');
                    $this->addFlash(
                      'danger',
                      'file does not have mandatory fields. ['.implode(', ', $templateFields).']'
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
            'file_type' => $entity,
        ));
    }


}
