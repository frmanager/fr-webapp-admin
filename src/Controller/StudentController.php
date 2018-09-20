<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use App\Entity\Student;
use App\Entity\Campaign;
use App\Utils\ValidationHelper;
use App\Utils\CSVHelper;
use App\Utils\QueryHelper;
use App\Utils\CampaignHelper;
use DateTime;

/**
 * Student controller.
 *
 * @Route("/{campaignUrl}/students")
 */
class StudentController extends Controller
{
    /**
     * Lists all Student entities.
     *
     * @Route("/", name="student_index")
     * @Method("GET")
     */
    public function indexAction($campaignUrl, LoggerInterface $logger)
    {
        
        $entity = 'Student';

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

        return $this->render('student/student.index.html.twig', array(
            'students' => $queryHelper->getStudentRanks(array('campaign' => $campaign,'limit'=> 0)),
            'entity' => $entity,
            'campaign' => $campaign,
        ));
    }

    /**
     * Creates a new Student entity.
     *
     * @Route("/new", name="student_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, $campaignUrl, LoggerInterface $logger)
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

        $student = new Student();

        if ($request->isMethod('POST')) {
            $params = $request->request->all();
            $fail = false;

            if(!$fail && empty($params['student']['name'])){
              $this->addFlash('warning','Student name is required');
              $fail = true;
            }

            if(!$fail && empty($params['student']['classroomID'])){
              $this->addFlash('warning','Classroom is required');
              $fail = true;
            }else{
              $classroom = $em->getRepository('App:Classroom')->findOneBy(array('id'=>$params['student']['classroomID'], 'campaign' => $campaign));
              if(is_null($classroom)){
                $this->addFlash('warning','No Valid Classroom was selected');
                $fail = true;
              }
            }


            if(!$fail){

              $student->setName($params['student']['name']);
              $student->setClassroom($classroom);
              $student->setGrade($classroom->getGrade());
              $student->setCampaign($campaign);

              $em->persist($student);
              $em->flush();
              return $this->redirectToRoute('student_index', array('campaignUrl'=> $campaignUrl));

            }

        }

        return $this->render('student/student.form.html.twig', array(
            'student' => $student,
            'classrooms' => $em->getRepository('App:Classroom')->findBy(array("campaign"=>$campaign)),
            'campaign' => $campaign,
        ));
    }

    /**
     * Finds and displays a Student entity.
     *
     * @Route("/{id}", name="student_show")
     * @Method("GET")
     */
    public function showAction(Student $student, $campaignUrl, LoggerInterface $logger)
    {
        
        $entity = "student";
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

        $deleteForm = $this->createDeleteForm($student, $campaignUrl);
        $student = $this->getDoctrine()->getRepository('App:'.strtolower($entity))->findOneById($student->getId());
        //$logger->debug(print_r($student->getDonations()));

        $qb = $em->createQueryBuilder()->select('u')
               ->from('App:Campaignaward', 'u')
               ->where('u.campaign = :campaign')
               ->setParameter('campaign', $campaign->getId())
               ->orderBy('u.amount', 'DESC');


        $campaign = $em->getRepository('App:Campaign')->findOneByUrl($campaignUrl);
        $campaignAwards = $qb->getQuery()->getResult();

        $queryHelper = new QueryHelper($em);

        return $this->render('student/student.show.html.twig', array(
            'student' => $student,
            'classroom' => $queryHelper->getClassroomsData(array('campaign' => $campaign, 'id' => $student->getClassroom()->getId())),
            'student_rank' => $queryHelper->getStudentRank($student->getId(),array('campaign' => $campaign, 'limit' => 0)),
            'classroom_rank' => $queryHelper->getClassroomRank($student->getClassroom()->getId(),array('campaign' => $campaign, 'limit' => 0)),
            'campaign_awards' => $campaignAwards,
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
            'campaign' => $campaign,
        ));
    }

    /**
     * Displays a form to edit an existing Student entity.
     *
     * @Route("/edit/{studentID}", name="student_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, $campaignUrl, $studentID, LoggerInterface $logger)
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

        //CODE TO CHECK TO SEE IF STUDENT EXISTS
        $student = $em->getRepository('App:Student')->find($studentID);
        if(is_null($student)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this student.');
          return $this->redirectToRoute('homepage');
        }

        if ($request->isMethod('POST')) {
            $params = $request->request->all();
            $fail = false;

            if(!$fail && empty($params['student']['name'])){
              $this->addFlash('warning','Classroom name is required');
              $fail = true;
            }

            if(!$fail && empty($params['student']['classroomID'])){
              $this->addFlash('warning','Classroom is required');
              $fail = true;
            }else{
              $classroom = $em->getRepository('App:Classroom')->findOneBy(array('id'=>$params['student']['classroomID'], 'campaign' => $campaign));
              if(is_null($classroom)){
                $this->addFlash('warning','No Valid Classroom was selected');
                $fail = true;
              }
            }

            if(!$fail){

              $student->setName($params['student']['name']);
              $student->setClassroom($classroom);
              $student->setGrade($classroom->getGrade());
              $student->setCampaign($campaign);

              $em->persist($student);
              $em->flush();
              return $this->redirectToRoute('student_index', array('campaignUrl'=> $campaignUrl));

            }

        }

        return $this->render('student/student.form.html.twig', array(
            'student' => $student,
            'classrooms' => $em->getRepository('App:Classroom')->findBy(array("campaign"=>$campaign)),
            'campaign' => $campaign,
        ));
    }

    /**
     * Deletes a Student entity.
     *
     * @Route("/delete/{id}", name="student_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Student $student, $campaignUrl, LoggerInterface $logger)
    {
        $entity = 'Student';
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

        $form = $this->createDeleteForm($student, $campaignUrl);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($student);
            $em->flush();
        }

        return $this->redirectToRoute(strtolower($entity).'_index');
    }

    /**
     * Creates a form to delete a Student entity.
     *
     * @param Student $student The Student entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Student $student, $campaignUrl)
    {
        $entity = 'Student';

        return $this->createFormBuilder()
            ->setAction($this->generateUrl('student_delete', array('campaignUrl'=> $campaignUrl, 'id' => $student->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Creates a new Student entity.
     *
     * @Route("/upload", name="student_upload")
     * @Method({"GET", "POST"})
     */
    public function uploadForm(Request $request, $campaignUrl, LoggerInterface $logger)
    {
        
        $entity = 'Student';
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
                $templateFields = array('students_name', 'grade', 'classrooms_name');

                if ($CSVHelper->validateHeaders($templateFields)) {
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
                            'The Causevox Teams table has been truncated'
                        );
                    }

                    $logger->info('Uploading Data');
                    $em = $this->getDoctrine()->getManager();
                    $errorMessages = [];
                    $errorMessage;
                    foreach ($CSVHelper->getData() as $i => $item) {
                        $failure = false;
                        unset($errorMessage);

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
                            $classroom = $this->getDoctrine()->getRepository('App:Classroom')->findOneByClassroomName($item['classrooms_name']);
                            if (empty($classroom)) {
                                $failure = true;
                                $errorMessage = new ValidationHelper(array(
                              'entity' => $entity,
                              'row_index' => ($i + 2),
                              'error_field' => 'classrooms_name',
                              'error_field_value' => $item['classrooms_name'],
                              'error_message' => 'Could not find classroom',
                              'error_level' => ValidationHelper::$level_error, ));
                            }
                        }

                        if (!$failure) {
                            $student = $this->getDoctrine()->getRepository('App:'.$entity)->findOneBy(
                        array('classroom' => $classroom, 'name' => $item['students_name'])
                        );
                        //Going to perform "Insert" vs "Update"
                          if (empty($student)) {
                              $logger->debug($entity.' not found....creating new record');
                              $student = new Student();
                          } else {
                              $logger->debug($entity.' found....updating existing record');
                              if (strcmp($mode, 'truncate') == 0) {
                                  $errorMessage = new ValidationHelper(array(
                                'entity' => $entity,
                                'row_index' => ($i + 2),
                                'error_field' => 'students_name',
                                'error_field_value' => $item['students_name'],
                                'error_message' => 'Duplicate with Student #'.$student->getId(),
                                'error_level' => ValidationHelper::$level_warning, ));
                              }
                          }
                            if (!$failure) {
                                $student->setName($item['students_name']);
                                $student->setClassroom($classroom);

                                $validator = $this->get('validator');
                                $errors = $validator->validate($student);

                                if (strcmp($mode, 'validate') !== 0) {
                                    if (count($errors) > 0) {
                                        $errorsString = (string) $errors;
                                        $logger->error('[ROW #'.($i + 2).'] Could not add ['.$entity.']: '.$errorsString);
                                        $this->addFlash(
                                        'danger',
                                        '[ROW #'.($i + 2).'] Could not add ['.$entity.']: '.$errorsString
                                    );
                                    } else {
                                        $em->persist($student);
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
