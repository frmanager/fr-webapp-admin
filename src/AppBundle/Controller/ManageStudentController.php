<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Student;
use AppBundle\Entity\Campaignsetting;
use AppBundle\Utils\ValidationHelper;
use AppBundle\Utils\CampaignHelper;
use AppBundle\Utils\CSVHelper;
use AppBundle\Utils\QueryHelper;
use DateTime;

/**
 * Student controller.
 *
 * @Route("/manage/{campaignUrl}/students")
 */
class ManageStudentController extends Controller
{
    /**
     * Lists all Student entities.
     *
     * @Route("/", name="manageStudent_index")
     * @Method("GET")
     */
    public function indexAction($campaignUrl)
    {
        $logger = $this->get('logger');
        $entity = 'Student';

        $em = $this->getDoctrine()->getManager();
        $queryHelper = new QueryHelper($em, $logger);
        $tempDate = new DateTime();
        $dateString = $tempDate->format('Y-m-d').' 00:00:00';
        $reportDate = DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
        // replace this example code with whatever you need

        return $this->render('campaignManager/student.index.html.twig', array(
            'students' => $queryHelper->getStudentRanks(array('limit'=> 0)),
            'entity' => $entity,
            'campaign' => $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl),
        ));
    }

    /**
     * Creates a new Student entity.
     *
     * @Route("/new", name="manageStudent_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, $campaignUrl)
    {
        $entity = 'Student';
        $student = new Student();
        $form = $this->createForm('AppBundle\Form\StudentType', $student);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($student);
            $em->flush();

            return $this->redirectToRoute(strtolower($entity).'_show', array('id' => $student->getId()));
        }

        return $this->render('crud/new.html.twig', array(
            'student' => $student,
            'form' => $form->createView(),
            'entity' => $entity,
            'campaign' => $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl),
        ));
    }

    /**
     * Finds and displays a Student entity.
     *
     * @Route("/{id}", name="manageStudent_show")
     * @Method("GET")
     */
    public function showAction(Student $student, $campaignUrl)
    {
        $logger = $this->get('logger');
        $entity = 'Student';
        $deleteForm = $this->createDeleteForm($student, $campaignUrl);
        $student = $this->getDoctrine()->getRepository('AppBundle:'.strtolower($entity))->findOneById($student->getId());
        //$logger->debug(print_r($student->getDonations()));

          $em = $this->getDoctrine()->getManager();

        $qb = $em->createQueryBuilder()->select('u')
               ->from('AppBundle:Campaignaward', 'u')
               ->orderBy('u.amount', 'DESC');

        $campaignAwards = $qb->getQuery()->getResult();
        $campaignSettings = new CampaignHelper($this->getDoctrine()->getRepository('AppBundle:Campaignsetting')->findAll());

        $queryHelper = new QueryHelper($em, $logger);

        return $this->render('campaignManager/student.show.html.twig', array(
            'student' => $student,
            'teacher' => $queryHelper->getTeachersData(array('id' => $student->getTeacher()->getId())),
            'student_rank' => $queryHelper->getStudentRank($student->getId(),array('limit' => 0)),
            'teacher_rank' => $queryHelper->getTeacherRank($student->getTeacher()->getId(),array('limit' => 0)),
            'campaign_awards' => $campaignAwards,
            'campaignsettings' => $campaignSettings->getCampaignSettings(),
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
            'campaign' => $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl),
        ));
    }

    /**
     * Displays a form to edit an existing Student entity.
     *
     * @Route("/edit/{id}", name="manageStudent_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Student $student, $campaignUrl)
    {
        $entity = 'Student';
        $deleteForm = $this->createDeleteForm($student);
        $editForm = $this->createForm('AppBundle\Form\StudentType', $student);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($student);
            $em->flush();

            return $this->redirectToRoute(strtolower($entity).'_show', array('id' => $student->getId()));
        }

        return $this->render('crud/edit.html.twig', array(
            'student' => $student,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
            'campaign' => $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl),
        ));
    }

    /**
     * Deletes a Student entity.
     *
     * @Route("/delete/{id}", name="manageStudent_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Student $student, $campaignUrl)
    {
        $entity = 'Student';
        $form = $this->createDeleteForm($student);
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
            ->setAction($this->generateUrl('manageStudent_delete', array('campaignUrl'=> $campaignUrl, 'id' => $student->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Creates a new Student entity.
     *
     * @Route("/upload", name="manageStudent_upload")
     * @Method({"GET", "POST"})
     */
    public function uploadForm(Request $request)
    {
        $logger = $this->get('logger');
        $entity = 'Student';
        $mode = 'update';
        $form = $this->createForm('AppBundle\Form\UploadType', array('entity' => $entity, 'file_type' => $entity, 'role' => $this->getUser()->getRoles()));
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
                $templateFields = array('students_name', 'grade', 'teachers_name');

                if ($CSVHelper->validateHeaders($templateFields)) {
                    $em = $this->getDoctrine()->getManager();

                    if (strcmp($mode, 'truncate') == 0) {
                        $logger->info('User selected to [truncate] table');

                        $qb = $em->createQueryBuilder();
                        $qb->delete('AppBundle:'.$entity, 's');
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
                            $grade = $this->getDoctrine()->getRepository('AppBundle:Grade')->findOneByName($item['grade']);
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
                            $teacher = $this->getDoctrine()->getRepository('AppBundle:Teacher')->findOneByTeacherName($item['teachers_name']);
                            if (empty($teacher)) {
                                $failure = true;
                                $errorMessage = new ValidationHelper(array(
                              'entity' => $entity,
                              'row_index' => ($i + 2),
                              'error_field' => 'teachers_name',
                              'error_field_value' => $item['teachers_name'],
                              'error_message' => 'Could not find teacher',
                              'error_level' => ValidationHelper::$level_error, ));
                            }
                        }

                        if (!$failure) {
                            $student = $this->getDoctrine()->getRepository('AppBundle:'.$entity)->findOneBy(
                        array('teacher' => $teacher, 'name' => $item['students_name'])
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
                                $student->setTeacher($teacher);

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
