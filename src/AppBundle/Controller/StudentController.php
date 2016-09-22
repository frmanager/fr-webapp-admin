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
/**
 * Student controller.
 *
 * @Route("/manage/student")
 */
class StudentController extends Controller
{
    /**
     * Lists all Student entities.
     *
     * @Route("/", name="student_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $logger = $this->get('logger');
        $entity = 'Student';
        $em = $this->getDoctrine()->getManager();

/* WORKING query

SELECT s.id as student_id,
       s.name as student_name,
       t.id as teacher_id,
       t.teacher_name as teacher_name,
       g.id as grade_id,
       g.name as grade_name,
       sum(d.amount) as donation_amount,
       count(d.amount) as total_donations
  FROM student s
  LEFT OUTER JOIN teacher t
    ON t.id = s.teacher_id
  LEFT OUTER JOIN donation d
    ON s.id = d.student_id
  LEFT OUTER JOIN grade g
    ON g.id = t.grade_id
 GROUP BY t.teacher_name
 ORDER BY g.name,
          t.teacher_name

*/

        $query = $em->createQuery('SELECT s.id as student_id,
                                          s.name as student_name,
                                          t.id as teacher_id,
                                          t.teacherName as teacher_name,
                                          g.id as grade_id,
                                          g.name as grade_name,
                                          sum(d.amount) as donation_amount,
                                          count(d.amount) as total_donations
                                     FROM AppBundle:Student s
                          LEFT OUTER JOIN AppBundle:Teacher t
                                     WITH t.id = s.teacher
                          LEFT OUTER JOIN AppBundle:Donation d
                                     WITH s.id = d.student
                          LEFT OUTER JOIN AppBundle:Grade g
                                     WITH g.id = t.grade
                                 GROUP BY s.id
                                 ORDER BY g.name,
                                          t.teacherName,
                                          s.name');

        $students = $query->getResult();




        //sorting by amount for rank....
        $query = $em->createQuery('SELECT s.id as student_id,
                                          sum(d.amount) as donation_amount,
                                          count(d.amount) as total_donations
                                     FROM AppBundle:Student s
                          LEFT OUTER JOIN AppBundle:Teacher t
                                     WITH t.id = s.teacher
                          LEFT OUTER JOIN AppBundle:Donation d
                                     WITH s.id = d.student
                                 GROUP BY s.id
                                 ORDER BY donation_amount DESC');

        $studentSorts = $query->getResult();

        $studentRank = 0;
        $amount = 9999999999999999999; //some astronomical number
        foreach ($studentSorts as $studentSort) {
            $logger->debug('Current Rank: '.$studentRank.' Current Donation Amount: '.$studentSort['donation_amount'].' amount to beat: '.$amount);
            if ($studentSort['donation_amount'] < $amount) {
                ++$studentRank;
            }

            foreach ($students as &$student) {
                if ($student['student_id'] == $studentSort['student_id']) {
                    $student['rank'] = $studentRank;
                    break;
                }
            }
            $amount = $studentSort['donation_amount'];
        }




        return $this->render(strtolower($entity).'/index.html.twig', array(
            'students' => $students,
            'entity' => $entity,
        ));
    }

    /**
     * Creates a new Student entity.
     *
     * @Route("/new", name="student_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
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
        ));
    }

    /**
     * Finds and displays a Student entity.
     *
     * @Route("/show/{id}", name="student_show")
     * @Method("GET")
     */
    public function showAction(Student $student)
    {
        $logger = $this->get('logger');
        $entity = 'Student';
        $deleteForm = $this->createDeleteForm($student);
        $student = $this->getDoctrine()->getRepository('AppBundle:'.strtolower($entity))->findOneById($student->getId());
        //$logger->debug(print_r($student->getDonations()));


          $em = $this->getDoctrine()->getManager();

          $qb = $em->createQueryBuilder()->select('u')
               ->from('AppBundle:Campaignaward', 'u')
               ->orderBy('u.amount', 'DESC');

        $campaignAwards = $qb->getQuery()->getResult();

        $data['total_class_donation_amount'] = 0;
        $data['total_class_donations'] = 0;

        $query = $em->createQuery('SELECT sum(d.amount) as donation_amount,
                                          count(d.amount) as total_donations
                                     FROM AppBundle:Teacher t
                          LEFT OUTER JOIN AppBundle:Student s
                                     WITH t.id = s.teacher
                          LEFT OUTER JOIN AppBundle:Donation d
                                     WITH s.id = d.student
                          LEFT OUTER JOIN AppBundle:Grade g
                                     WITH g.id = t.grade
                                    WHERE t.id = '.$student->getTeacher()->getId().'
                                 GROUP BY t.id
                                 ORDER BY t.teacherName');

        $results = $query->getResult();

        $campaignSettings = new CampaignHelper($this->getDoctrine()->getRepository('AppBundle:Campaignsetting')->findAll());


        $query = $em->createQuery('SELECT s.id as student_id,
                                          sum(d.amount) as donation_amount
                                     FROM AppBundle:Student s
                          LEFT OUTER JOIN AppBundle:Donation d
                                     WITH s.id = d.student
                                 GROUP BY s.id
                                 ORDER BY donation_amount DESC');

        $items = $query->getResult();

        $studentRank = 0;
        $amount = 9999999999999999999; //some astronomical number

        foreach ($items as $item) {
            $logger->debug('Current Rank: '.$studentRank.' Current Donation Amount: '.$item['donation_amount'].' amount to beat: '.$amount);
            if ($item['donation_amount'] < $amount) {
                ++$studentRank;
            }

            if ($student->getId() == $item['student_id']) {
                break;
            }

            $amount = $item['donation_amount'];
        }







        $query = $em->createQuery('SELECT t.id as teacher_id,
                                          t.teacherName as teacher_name,
                                          sum(d.amount) as donation_amount
                                     FROM AppBundle:Teacher t
                          LEFT OUTER JOIN AppBundle:Student s
                                     WITH t.id = s.teacher
                          LEFT OUTER JOIN AppBundle:Donation d
                                     WITH s.id = d.student
                                 GROUP BY t.teacherName
                                 ORDER BY donation_amount DESC');

        $items = $query->getResult();

        $teacherRank = 0;
        $amount = 9999999999999999999; //some astronomical number
        foreach ($items as $item) {
            $logger->debug('Current Rank: '.$teacherRank.' Current Donation Amount: '.$item['donation_amount'].' amount to beat: '.$amount);
            if ($item['donation_amount'] < $amount) {
                ++$teacherRank;
            }

            if ($student->getTeacher()->getId() == $item['teacher_id']) {
                break;
            }

            $amount = $item['donation_amount'];
        }










        return $this->render(strtolower($entity).'/show.html.twig', array(
            'total_class_donation_amount' => $results[0]['donation_amount'],
            'total_class_donations' => $results[0]['total_donations'],
            'student' => $student,
            'student_rank' => $studentRank,
            'teacher_rank' => $teacherRank,
            'campaign_awards' => $campaignAwards,
            'campaignsettings' => $campaignSettings->getCampaignSettings(),
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing Student entity.
     *
     * @Route("/edit/{id}", name="student_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Student $student)
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
        ));
    }

    /**
     * Deletes a Student entity.
     *
     * @Route("/delete/{id}", name="student_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Student $student)
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
    private function createDeleteForm(Student $student)
    {
        $entity = 'Student';

        return $this->createFormBuilder()
            ->setAction($this->generateUrl(strtolower($entity).'_delete', array('id' => $student->getId())))
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
    public function uploadForm(Request $request)
    {
        $logger = $this->get('logger');
        $entity = 'Student';
        $mode = 'update';
        $form = $this->createForm('AppBundle\Form\UploadType', array('entity' => $entity, 'file_type' => $entity));
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

                $csvHelper = new csvHelper();
                $csvHelper->processFile('temp/', strtolower($entity).'.csv');
                $templateFields = array('students_name', 'grade', 'teachers_name');

                if ($csvHelper->validateHeaders($templateFields)) {
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
                    foreach ($csvHelper->getData() as $i => $item) {
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
                              $errorMessage = new ValidationHelper(array(
                                'entity' => $entity,
                                'row_index' => ($i + 2),
                                'error_field' => 'students_name',
                                'error_field_value' => $item['students_name'],
                                'error_message' => 'Duplicate with Student #'.$student->getId(),
                                'error_level' => ValidationHelper::$level_warning, ));
                          }

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
