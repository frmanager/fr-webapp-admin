<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Causevoxdonation;
use AppBundle\Entity\Grade;
use AppBundle\Entity\Student;
use AppBundle\Utils\CSVHelper;
use AppBundle\Utils\ValidationHelper;
use DateTime;

/**
 * Causevoxdonation controller.
 *
 * @Route("/manage/causevoxdonation")
 */
class CausevoxdonationController extends Controller
{
    /**
     * Lists all Causevoxdonation entities.
     *
     * @Route("/", name="causevoxdonation_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $entity = 'Causevoxdonation';
        $em = $this->getDoctrine()->getManager();

        $causevoxdonations = $em->getRepository('AppBundle:'.$entity)->findAll();

        return $this->render(strtolower($entity).'/index.html.twig', array(
            'causevoxdonations' => $causevoxdonations,
            'entity' => $entity,
        ));
    }

    /**
     * Creates a new Causevoxdonation entity.
     *
     * @Route("/new", name="causevoxdonation_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $entity = 'Causevoxdonation';
        $causevoxdonation = new Causevoxdonation();
        $form = $this->createForm('AppBundle\Form\CausevoxdonationType', $causevoxdonation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($causevoxdonation);
            $em->flush();

            return $this->redirectToRoute(strtolower($entity).'_show', array('id' => $causevoxdonation->getId()));
        }

        return $this->render('crud/new.html.twig', array(
            'causevoxdonation' => $causevoxdonation,
            'form' => $form->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Finds and displays a Causevoxdonation entity.
     *
     * @Route("/show/{id}", name="causevoxdonation_show")
     * @Method("GET")
     */
    public function showAction(Causevoxdonation $causevoxdonation)
    {
        $entity = 'Causevoxdonation';
        $deleteForm = $this->createDeleteForm($causevoxdonation);

        return $this->render(strtolower($entity).'/show.html.twig', array(
            'causevoxdonation' => $causevoxdonation,
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing Causevoxdonation entity.
     *
     * @Route("/edit/{id}", name="causevoxdonation_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Causevoxdonation $causevoxdonation)
    {
        $entity = 'Causevoxdonation';
        $deleteForm = $this->createDeleteForm($causevoxdonation);
        $editForm = $this->createForm('AppBundle\Form\CausevoxdonationType', $causevoxdonation);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($causevoxdonation);
            $em->flush();

            return $this->redirectToRoute(strtolower($entity).'_edit', array('id' => $causevoxdonation->getId()));
        }

        return $this->render('crud/edit.html.twig', array(
            'causevoxdonation' => $causevoxdonation,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Deletes a Causevoxdonation entity.
     *
     * @Route("/delete/{id}", name="causevoxdonation_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Causevoxdonation $causevoxdonation)
    {
        $entity = 'Causevoxdonation';
        $form = $this->createDeleteForm($causevoxdonation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($causevoxdonation);
            $em->flush();
        }

        return $this->redirectToRoute(strtolower($entity).'_index');
    }

    /**
     * Creates a form to delete a Causevoxdonation entity.
     *
     * @param Causevoxdonation $causevoxdonation The Causevoxdonation entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Causevoxdonation $causevoxdonation)
    {
        $entity = 'Causevoxdonation';

        return $this->createFormBuilder()
            ->setAction($this->generateUrl(strtolower($entity).'_delete', array('id' => $causevoxdonation->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Upload multiple Causevoxdonation via CSV File.
     *
     * @Route("/upload", name="causevoxdonation_upload")
     * @Method({"GET", "POST"})
     */
    public function uploadForm(Request $request)
    {
        $logger = $this->get('logger');
        $entity = 'Causevoxdonation';
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
                $csvHelper->processFile('temp/', strtolower($entity).'.csv');
                $csvHelper->getGradefromTeacherName();
                $csvHelper->cleanTeacherNames();
                $logger->debug(print_r($csvHelper->getData(), true));

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
                'teachers_name',
                'students_name', );

                if ($csvHelper->validateHeaders($templateFields)) {
                    $logger->debug('Making changes to database');
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
                            'The Causevox Donations table has been truncated'
                        );
                    }

                    $logger->info('Uploading Data');
                    $em = $this->getDoctrine()->getManager();
                    $errorMessages = [];
                    $errorMessage;
                    foreach ($csvHelper->getData() as $i => $item) {
                        $failure = false;
                        unset($errorMessage);

                        if (null == $item['donation_page'] || empty($item['donation_page']) || strcmp('none', $item['donation_page']) == 0) {
                            $logger->debug('Donation Page: '.$item['donation_page']);
                            $failure = true;
                            $errorMessage = new ValidationHelper(array(
                              'row_index' => ($i + 2),
                              'error_field' => 'donation_page',
                              'error_field_value' => 'null',
                              'error_message' => 'Donation Page URL Required',
                              'error_level' => ValidationHelper::$level_error, ));

                            //$validations{}
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
                          $date = new DateTime($item['donated_at']);

                            $causevoxdonation = $this->getDoctrine()->getRepository('AppBundle:'.$entity)->findOneBy(
                          array('donatedAt' => $date, 'student' => $student, 'teacher' => $teacher)
                          );
                          //Going to perform "Insert" vs "Update"
                            if (empty($causevoxdonation)) {
                                $logger->debug($entity.' not found....creating new record');
                                $causevoxdonation = new Causevoxdonation();
                            } else {
                                $logger->debug($entity.' not found....updating existing record');
                                $errorMessage = new ValidationHelper(array(
                                  'entity' => $entity,
                                  'row_index' => ($i + 2),
                                  'error_field' => 'N/A',
                                  'error_field_value' => 'N/A',
                                  'error_message' => 'Duplicate with Causvox Donation #'.$causevoxdonation->getId(),
                                  'error_level' => ValidationHelper::$level_warning, ));
                            }

                            $causevoxdonation->setAmount($item['amount']);
                            $causevoxdonation->setTip($item['tip']);
                            $causevoxdonation->setEstimatedCcFee($item['est_cc_fee']);
                            $causevoxdonation->setCausevoxFee($item['causevox_fee']);
                            $causevoxdonation->setType($item['type']);
                            $causevoxdonation->setDonorFirstName($item['donor_first_name']);
                            $causevoxdonation->setDonorLastName($item['donor_last_name']);
                            $causevoxdonation->setDonorEmail($item['donor_email']);
                            $causevoxdonation->setDonorComment($item['donor_comment']);
                            $causevoxdonation->setDonationPage($item['donation_page']);
                            $causevoxdonation->setDonatedAt($date);
                            $causevoxdonation->setStudent($student);
                            $causevoxdonation->setTeacher($teacher);

                            $validator = $this->get('validator');
                            $errors = $validator->validate($causevoxdonation);

                            if (strcmp($mode, 'validate') !== 0) {
                                if (count($errors) > 0) {
                                    $errorsString = (string) $errors;
                                    $logger->error('[ROW #'.($i + 2).'] Could not add ['.$entity.']: '.$errorsString);
                                    $this->addFlash(
                                        'danger',
                                        '[ROW #'.($i + 2).'] Could not add ['.$entity.']: '.$errorsString
                                    );
                                } else {
                                    $em->persist($causevoxdonation);
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
                    $logger->info('file does not have mandatory fields. Please verify it was downloaded from Causevox');
                    $logger->info('File was not a .csv');
                    $this->addFlash(
                        'danger',
                        'file does not have mandatory fields. Please verify it was downloaded from Causevox'
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
