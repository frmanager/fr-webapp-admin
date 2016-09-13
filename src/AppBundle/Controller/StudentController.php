<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Student;

/**
 * Student controller.
 *
 * @Route("/admin/student")
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

            return $this->redirectToRoute(strtolower($entity).'_edit', array('id' => $student->getId()));
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
        $form = $this->createForm('AppBundle\Form\UploadType', array('entity' => $entity));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $form['entity']->getData();
            $uploadFile = $form['attachment']->getData();

            if (strpos($uploadFile->getClientOriginalName(), '.csv') !== false) {
                $logger->info('File was a .csv, attempting to load');

                $uploadFile->move('temp/', strtolower($entity).'.csv');
                $csvFile = fopen('temp/'.strtolower($entity).'.csv', 'r');

                $counter = 0;
                $fileData = [];
                $thisRow;
                $fileLabels;

                while (!feof($csvFile)) {
                    $thisRow = fgetcsv($csvFile);
                    //$logger->info(print_r($thisRow, true));
                  //Skip Empty Rows
                  if (!empty($thisRow)) {
                      $thisRowAsObjects = [];
                      if ($counter == 0) {
                          foreach ($thisRow as $key => $value) {
                              $fileLabels[$key] = $this->clean($value);
                          }
                      } else {
                          foreach ($thisRow as $key => $value) {
                              $thisRowAsObjects[$this->clean($fileLabels[$key])] = trim($value);
                          }
                          array_push($fileData, $thisRowAsObjects);
                      }
                  }
                    ++$counter;
                }
                fclose($csvFile);
                $logger->info(print_r($fileLabels, true));
                if (in_array('name', $fileLabels) || in_array('grade', $fileLabels) || in_array('teachers_name', $fileLabels)) {
                    $logger->info('Making changes to database');
                    $logger->info('Clearing Table.');

                    $em = $this->getDoctrine()->getManager();
                    $qb = $em->createQueryBuilder();
                    $qb->delete('AppBundle:Student', 's');
                    $query = $qb->getQuery();

                    if ($query->getResult() == 0) {
                        $logger->info('Something Happened');
                    }
                    $em->flush();
                    $logger->info('Uploading Data');
                    $em = $this->getDoctrine()->getManager();
                    $batchSize = 20;

                    foreach ($fileData as $i => $item) {
                        $student = new Student();
                        $grade = $this->getDoctrine()->getRepository('AppBundle:Grade')->findOneByName($item['grade']);

                        if (empty($grade)) {
                            $this->addFlash(
                                'danger',
                                "Could not add student '".$item['name']."' with teacher's name '".$item['teachers_name']."'"
                            );
                        } else {
                            $teacher = $this->getDoctrine()->getRepository('AppBundle:Teacher')->findOneBy(
                                array('teacherName' => $item['teachers_name'], 'grade' => $grade->getId())
                            );
                            if (empty($teacher)) {
                                $this->addFlash(
                                    'danger',
                                    "Could not add student '".$item['name']."' with teacher's name '".$item['teachers_name']."'"
                                );
                            } else {
                                $student->setName($item['name']);
                                $student->setTeacher($teacher);
                                $em->persist($student);

                             // flush everything to the database every 20 inserts
                             if (($i % $batchSize) == 0) {
                                 $em->flush();
                                 $em->clear();
                             }
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

    private function clean($string)
    {
        $string = str_replace(' ', '_', $string); // Replaces all spaces with underscores.
        $string = preg_replace('/[^A-Za-z0-9\_]/', '', $string); // Removes special chars.
        $string = trim($string);

        return strtolower($string);
    }
}
