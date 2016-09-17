<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Student;
use AppBundle\Utils\CSVHelper;

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
        $entity = 'Student';
        $em = $this->getDoctrine()->getManager();

        $students = $em->getRepository('AppBundle:Student')->findAll();

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
        $entity = 'Student';
        $deleteForm = $this->createDeleteForm($student);

        return $this->render(strtolower($entity).'/show.html.twig', array(
            'student' => $student,
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
        $truncateFlag = false;
        $logger = $this->get('logger');
        $failure = false;
        $entity = 'Student';
        $form = $this->createForm('AppBundle\Form\UploadType', array('entity' => $entity));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (null != $form['truncate_table']->getData()) {
                $str = $form['truncate_table']->getData();
                if (in_array('truncate_yes', $str)) {
                    $truncateFlag = true;
                    $logger->info('Truncate table set to true');
                }
            }
            $entity = $form['entity']->getData();
            $uploadFile = $form['attachment']->getData();

            if (strpos($uploadFile->getClientOriginalName(), '.csv') !== false) {
                $logger->info('File was a .csv, attempting to load');

                $uploadFile->move('temp/', strtolower($entity).'.csv');

                $csvHelper = new csvHelper();
                $csvHelper->processFile('temp/', strtolower($entity).'.csv');
                $logger->debug(print_r($csvHelper->getData(), true));
                $templateFields = array('name', 'grade', 'teachers_name');

                if ($csvHelper->validateHeaders($templateFields)) {
                    $em = $this->getDoctrine()->getManager();

                    if ($truncateFlag) {
                        $logger->info('Clearing Table.');
                        $students = $em->getRepository('AppBundle:Student')->findAll();
                        foreach ($students as $student) {
                            $em->remove($student);
                        }
                        $em->flush();
                        $em->clear();
                        $this->addFlash(
                          'info',
                          'Students table truncated'
                      );
                    }

                    $logger->debug('Getting Teachers');
                    $em = $this->getDoctrine()->getManager();
                    $teachers = $em->getRepository('AppBundle:Teacher')->findAll();
                    $batchSize = 20;

                    foreach ($csvHelper->getData() as $i => $item) {
                        $failure = false;
                        $teacher;
                        //$grade = $this->getDoctrine()->getRepository('AppBundle:Grade')->findByName($item['grade']);

                        $teacher = $this->getDoctrine()->getRepository('AppBundle:Teacher')->findOneByTeacherName($item['teachers_name']);

                        if (!isset($teacher)) {
                            $failure = true;
                            $this->addFlash(
                            'danger',
                            '[ROW #'.($i + 2).'] Could not add student '.$item['name'].', teacher '.$item['teachers_name'].' could not be found with Grade '.$item['grade']
                        );
                        }

                        if (!$failure) {
                            $student = new Student();

                            $student->setName($item['name']);
                            $student->setTeacher($teacher);

                            $validator = $this->get('validator');
                            $errors = $validator->validate($student);

                            if (count($errors) > 0) {
                                /*
                                 * Uses a __toString method on the $errors variable which is a
                                 * ConstraintViolationList object. This gives us a nice string
                                 * for debugging.
                                 */
                                $errorsString = (string) $errors;
                                $this->addFlash('danger', '[ROW #'.($i + 2).'] Could not add student '.$item['name'].' for teacher '.$item['teachers_name'].', error:'.$errorsString);
                            } else {
                                $em->persist($student);
                                $em->flush();
                                $em->clear();
                            }
                        }
                    }

                    // flush the remaining objects
                    $em->flush();
                    $em->clear();

                    $this->addFlash(
                        'info',
                        'Completed'
                    );

                    return $this->redirectToRoute(strtolower($entity).'_index');
                } else {
                    $logger->info('file does not have mandatory "Grade", "Name", and "Teachers Name" fields');
                }
            } else {
                $logger->info('File was not a .csv');
                $this->addFlash(
                    'danger',
                    'File was not a .csv'
                );
            }

            return $this->render('crud/upload.html.twig', array(
                'form' => $form->createView(),
                'entity' => $entity,
            ));
        }

        return $this->render('crud/upload.html.twig', array(
            'form' => $form->createView(),
            'entity' => $entity,
        ));
    }
}
