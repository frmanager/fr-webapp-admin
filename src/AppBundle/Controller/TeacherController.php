<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Teacher;
use AppBundle\Entity\Grade;

/**
 * Teacher controller.
 *
 * @Route("/admin/teacher")
 */
class TeacherController extends Controller
{
    /**
     * Lists all Teacher entities.
     *
     * @Route("/", name="teacher_index")
     * @Method({"GET", "POST"})
     */
    public function indexAction()
    {
        $entity = 'Teacher';
        $em = $this->getDoctrine()->getManager();
        $teachers = $em->getRepository('AppBundle:Teacher')->findAll();

        return $this->render(strtolower($entity).'/index.html.twig', array(
            'teachers' => $teachers,
            'entity' => $entity,
        ));
    }

    /**
     * Creates a new Teacher entity.
     *
     * @Route("/new", name="teacher_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $entity = 'Teacher';
        $teacher = new Teacher();
        $form = $this->createForm('AppBundle\Form\TeacherType', $teacher);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($teacher);
            $em->flush();

            return $this->redirectToRoute('teacher_index', array('id' => $teacher->getId()));
        }

        return $this->render('crud/new.html.twig', array(
            'teacher' => $teacher,
            'form' => $form->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Finds and displays a Teacher entity.
     *
     * @Route("/show/{id}", name="teacher_show")
     * @Method("GET")
     */
    public function showAction(Teacher $teacher)
    {
        $entity = 'Teacher';
        $deleteForm = $this->createDeleteForm($teacher);

        return $this->render(strtolower($entity).'/show.html.twig', array(
            'teacher' => $teacher,
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing Teacher entity.
     *
     * @Route("/edit/{id}", name="teacher_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Teacher $teacher)
    {
        $entity = 'Teacher';
        $deleteForm = $this->createDeleteForm($teacher);
        $editForm = $this->createForm('AppBundle\Form\TeacherType', $teacher);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($teacher);
            $em->flush();

            return $this->redirectToRoute('teacher_edit', array('id' => $teacher->getId()));
        }

        return $this->render('crud/edit.html.twig', array(
            'teacher' => $teacher,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Deletes a Teacher entity.
     *
     * @Route("/delete/{id}", name="teacher_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Teacher $teacher)
    {
        $entity = 'Teacher';
        $form = $this->createDeleteForm($teacher);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($teacher);
            $em->flush();
        }

        return $this->redirectToRoute(strtolower($entity).'_index');
    }

    /**
     * Creates a form to delete a Teacher entity.
     *
     * @param Teacher $teacher The Teacher entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Teacher $teacher)
    {
        $entity = 'Teacher';

        return $this->createFormBuilder()
            ->setAction($this->generateUrl(strtolower($entity).'_delete', array('id' => $teacher->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Creates a new Teacher entity.
     *
     * @Route("/upload", name="teacher_upload")
     * @Method({"GET", "POST"})
     */
    public function uploadForm(Request $request)
    {
        $logger = $this->get('logger');
        $entity = 'Teacher';
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
                if (in_array('teachers_name', $fileLabels) || in_array('grade', $fileLabels)) {
                    $logger->info('Making changes to database');
                    $logger->info('Clearing Table.');

                    $em = $this->getDoctrine()->getManager();
                    $qb = $em->createQueryBuilder();
                    $qb->delete('AppBundle:Teacher', 't');
                    $query = $qb->getQuery();

                    if ($query->getResult() == 0) {
                        $logger->info('Something Happened');
                    }
                    $em->flush();
                    $logger->info('Uploading Data');
                    $em = $this->getDoctrine()->getManager();
                    $batchSize = 20;

                    foreach ($fileData as $i => $item) {
                        $teacher = new Teacher();
                        $grade = $this->getDoctrine()->getRepository('AppBundle:Grade')->findOneByName($item['grade']);
                        if (empty($grade)) {
                            $this->addFlash(
                                'danger',
                                "Could not add teacher '".$item['teachers_name']."' with grade '".$item['grade']."'"
                            );
                        } else {
                            $teacher->setTeacherName($item['teachers_name']);
                            $teacher->setGrade($grade);
                            $em->persist($teacher);

                         // flush everything to the database every 20 inserts
                         if (($i % $batchSize) == 0) {
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
                    $logger->info('file does not have mandatory "Grade" and "Name" fields');
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
