<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Teacher;
use AppBundle\Entity\Grade;
use AppBundle\Utils\ValidationHelper;
use AppBundle\Utils\CSVHelper;

/**
 * Teacher controller.
 *
 * @Route("/manage/teacher")
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

            return $this->redirectToRoute('teacher_index', array('id' => $teacher->getId()));
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
                $templateFields = array('teachers_name', 'grade');

                if ($csvHelper->validateHeaders($templateFields)) {
                    $logger->info('Making changes to database');

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
                            'The Teachers table has been truncated'
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
                            $teacher = $this->getDoctrine()->getRepository('AppBundle:'.$entity)->findOneBy(
                      array('grade' => $grade->getId(), 'teacherName' => $item['teachers_name'])
                      );
                      //Going to perform "Insert" vs "Update"
                        if (empty($teacher)) {
                            $logger->debug($entity.' not found....creating new record');
                            $teacher = new Teacher();
                        } else {
                            $logger->debug($entity.' found....updating existing record');
                            $errorMessage = new ValidationHelper(array(
                              'entity' => $entity,
                              'row_index' => ($i + 2),
                              'error_field' => 'teachers_name',
                              'error_field_value' => $item['teachers_name'],
                              'error_message' => 'Duplicate with '.$entity.' #'.$teacher->getId(),
                              'error_level' => ValidationHelper::$level_warning, ));
                        }

                            $teacher->setTeacherName($item['teachers_name']);
                            $teacher->setGrade($grade);

                            $validator = $this->get('validator');
                            $errors = $validator->validate($teacher);

                            if (strcmp($mode, 'validate') !== 0) {
                                if (count($errors) > 0) {
                                    $errorsString = (string) $errors;
                                    $logger->error('[ROW #'.($i + 2).'] Could not add ['.$entity.']: '.$errorsString);
                                    $this->addFlash(
                                      'danger',
                                      '[ROW #'.($i + 2).'] Could not add ['.$entity.']: '.$errorsString
                                  );
                                } else {
                                    $em->persist($teacher);
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
