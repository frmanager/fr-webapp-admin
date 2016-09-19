<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Grade;
use AppBundle\Entity\Student;
use AppBundle\Utils\CSVHelper;
use AppBundle\Entity\Offlinedonation;
use AppBundle\Utils\ValidationHelper;
use DateTime;

/**
 * Offlinedonation controller.
 *
 * @Route("/manage/Offlinedonation")
 */
class OfflinedonationController extends Controller
{
    /**
     * Lists all Offlinedonation entities.
     *
     * @Route("/", name="offlinedonation_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $entity = 'Offlinedonation';
        $em = $this->getDoctrine()->getManager();

        $offlinedonations = $em->getRepository('AppBundle:Offlinedonation')->findAll();

        return $this->render(strtolower($entity).'/index.html.twig', array(
            'offlinedonations' => $offlinedonations,
            'entity' => $entity,
        ));
    }

    /**
     * Creates a new Offlinedonation entity.
     *
     * @Route("/new", name="offlinedonation_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $entity = 'Offlinedonation';
        $offlinedonation = new Offlinedonation();
        $form = $this->createForm('AppBundle\Form\OfflinedonationType', $offlinedonation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($offlinedonation);
            $em->flush();

            return $this->redirectToRoute(strtolower($entity).'_show', array('id' => $offlinedonation->getId()));
        }

        return $this->render('crud/new.html.twig', array(
            'offlinedonation' => $offlinedonation,
            'form' => $form->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Finds and displays a Offlinedonation entity.
     *
     * @Route("/show/{id}", name="offlinedonation_show")
     * @Method("GET")
     */
    public function showAction(Offlinedonation $offlinedonation)
    {
        $entity = 'Offlinedonation';
        $deleteForm = $this->createDeleteForm($offlinedonation);

        return $this->render(strtolower($entity).'/show.html.twig', array(
            'offlinedonation' => $offlinedonation,
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing Offlinedonation entity.
     *
     * @Route("/edit/{id}", name="offlinedonation_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Offlinedonation $offlinedonation)
    {
        $entity = 'Offlinedonation';
        $deleteForm = $this->createDeleteForm($offlinedonation);
        $editForm = $this->createForm('AppBundle\Form\OfflinedonationType', $offlinedonation);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($offlinedonation);
            $em->flush();

            return $this->redirectToRoute(strtolower($entity).'_edit', array('id' => $offlinedonation->getId()));
        }

        return $this->render('crud/edit.html.twig', array(
            'offlinedonation' => $offlinedonation,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Deletes a Offlinedonation entity.
     *
     * @Route("/delete/{id}", name="offlinedonation_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Offlinedonation $offlinedonation)
    {
        $entity = 'Offlinedonation';
        $form = $this->createDeleteForm($offlinedonation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($offlinedonation);
            $em->flush();
        }

        return $this->redirectToRoute(strtolower($entity).'_index');
    }

    /**
     * Creates a form to delete a Offlinedonation entity.
     *
     * @param Offlinedonation $offlinedonation The Offlinedonation entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Offlinedonation $offlinedonation)
    {
        $entity = 'Offlinedonation';

        return $this->createFormBuilder()
            ->setAction($this->generateUrl(strtolower($entity).'_delete', array('id' => $offlinedonation->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Upload multiple Offlinedonation via CSV File.
     *
     * @Route("/upload", name="offlinedonation_upload")
     * @Method({"GET", "POST"})
     */
    public function uploadForm(Request $request)
    {
        $logger = $this->get('logger');
        $entity = 'Offlinedonation';
        $mode = 'update';
        $form = $this->createForm('AppBundle\Form\UploadType', array('entity' => $entity));
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
                $csvHelper->setHeaderRowIndex(1);
                $csvHelper->processFile('temp/', strtolower($entity).'.csv');
                $csvHelper->cleanAmounts();

                $templateFields = array('date', 'grade', 'teachers_name', 'students_name', 'amount');

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
                            if (is_null($item['amount']) || !isset($item['amount']) || empty($item['amount']) || strcmp($item['amount'], '') == 0) {
                                $failure = true;
                              //We do not notify if amount is empty.....we just ignore it.
                            }
                        }

                        if (!$failure) {
                            if (!isset($item['date']) || empty($item['date']) || strcmp('none', $item['date']) == 0) {
                                $failure = true;
                                $errorMessage = new ValidationHelper(array(
                                'entity' => $entity,
                                'row_index' => ($i + 2),
                                'error_field' => 'date',
                                'error_field_value' => $item['date'],
                                'error_message' => 'Date cannot be null',
                                'error_level' => ValidationHelper::$level_error, ));
                            }
                        }

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
                            $student = $this->getDoctrine()->getRepository('AppBundle:Student')->findOneBy(
                          array('teacher' => $teacher, 'name' => $item['students_name'])
                        );
                            if (empty($student)) {
                                $failure = true;
                                $errorMessage = new ValidationHelper(array(
                              'entity' => $entity,
                              'row_index' => ($i + 2),
                              'error_field' => 'students_name',
                              'error_field_value' => $item['students_name'],
                              'error_message' => 'Could not find student',
                              'error_level' => ValidationHelper::$level_error, ));
                            }
                        }

                        if (!$failure) {

                          //Example: 2016-08-25 16:35:54
                          $date = new DateTime($item['date']);

                            $offlinedonation = $this->getDoctrine()->getRepository('AppBundle:'.$entity)->findOneBy(
                          array('student' => $student, 'donatedAt' => $date)
                          );
                          //Going to perform "Insert" vs "Update"
                          if (empty($offlinedonation)) {
                              $logger->debug($entity.' not found....creating new record');
                              $offlinedonation = new OfflineDonation();
                          } else {
                              $logger->debug($entity.' found....cannot update.');
                              $failure = true;
                              $errorMessage = new ValidationHelper(array(
                                'entity' => $entity,
                                'row_index' => ($i + 2),
                                'error_field' => 'N/A',
                                'error_field_value' => 'N/A',
                                'error_message' => 'A donation for this student and date already exists #'.$offlinedonation->getId(),
                                'error_level' => ValidationHelper::$level_error, ));
                          }
                        }
                        if (!$failure) {
                            $offlinedonation->setAmount($item['amount']);
                            $offlinedonation->setDonatedAt($date);
                            $offlinedonation->setStudent($student);

                            $validator = $this->get('validator');
                            $errors = $validator->validate($offlinedonation);

                            if (strcmp($mode, 'validate') !== 0) {
                                if (count($errors) > 0) {
                                    $errorsString = (string) $errors;
                                    $logger->error('[ROW #'.($i + 2).'] Could not add ['.$entity.']: '.$errorsString);
                                    $this->addFlash(
                                        'danger',
                                        '[ROW #'.($i + 2).'] Could not add ['.$entity.']: '.$errorsString
                                    );
                                } else {
                                    $em->persist($offlinedonation);
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
                    $logger->info('file does not have mandatory fields. ['.implode(', ', $templateFields).']. Please validate it was downloaded from the "FUNRUN LEDGER"');
                    $logger->info('File was not a .csv');
                    $this->addFlash(
                        'danger',
                        'file does not have mandatory fields. ['.implode(', ', $templateFields).']. Please validate it was downloaded from the "FUNRUN LEDGER"'
                    );
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
