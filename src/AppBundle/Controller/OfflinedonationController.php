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
        $truncateFlag = false;
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
            $uploadFile = $form['attachment']->getData();

            if (strpos($uploadFile->getClientOriginalName(), '.csv') !== false) {
                $logger->info('File was a .csv, attempting to load');
                $uploadFile->move('temp/', strtolower($entity).'.csv');
                $csvHelper = new csvHelper();
                $csvHelper->setHeaderRowIndex(1);
                $csvHelper->processFile('temp/', strtolower($entity).'.csv');
                $csvHelper->cleanAmounts();
                //$logger->debug(print_r($csvHelper->getData(), true));

                $templateFields = array('date', 'grade', 'teacher', 'student', 'amount');

                if ($csvHelper->validateHeaders($templateFields)) {
                    $em = $this->getDoctrine()->getManager();

                    if ($truncateFlag) {
                        $logger->info('Clearing Table.');

                        $qb = $em->createQueryBuilder();
                        $qb->delete('AppBundle:Offlinedonation', 's');
                        $query = $qb->getQuery();

                        if ($query->getResult() == 0) {
                            $logger->info('Something Happened');
                        }
                        $em->flush();

                        $this->addFlash(
                            'success',
                            'Offlinedonation table truncated Successfully'
                        );
                    }

                    $logger->info('Uploading Data');
                    $em = $this->getDoctrine()->getManager();
                    $breakLoop = false;

                    foreach ($csvHelper->getData() as $i => $item) {
                        $offlinedonation = new Offlinedonation();
                        $failure = false;

                        //ECHECKING FOR END ROW
                        if (strcmp('END', $item['date']) == 0) {
                            $failure = true;
                            $breakLoop = true;
                        }

                        //NO AMOUNT
                        if (!$failure) {
                            if (is_null($item['amount']) || empty($item['amount']) || strcmp($item['amount'], '') == 0) {
                                $failure = true;
                            }
                        }

                        if (!$failure) {
                            if (!isset($item['date']) || empty($item['date']) || strcmp('none', $item['date']) == 0) {
                                $failure = true;
                                $this->addFlash(
                              'danger',
                              '[ROW #'.($i + 3).'] Could not add Offlinedonation. Field: "DATE" required!'
                          );
                            }
                        }

                        if (!$failure) {
                            $grade = $this->getDoctrine()->getRepository('AppBundle:Grade')->findOneByName($item['grade']);
                            if (empty($grade)) {
                                $failure = true;
                                $this->addFlash(
                                  'danger',
                                  '[ROW #'.($i + 3).'] Could not add Offlinedonation. Grade '.$item['grade'].' not found'
                              );
                            }
                        }

                        if (!$failure) {
                            $teacher = $this->getDoctrine()->getRepository('AppBundle:Teacher')->findOneByTeacherName($item['teacher']);
                            if (empty($teacher)) {
                                $failure = true;
                                $this->addFlash(
                                  'danger',
                                    '[ROW #'.($i + 3).'] Could not add Offlinedonation. Teacher "'.$item['teacher'].'" not found'
                              );
                            }
                        }

                        if (!$failure) {
                            $student = $this->getDoctrine()->getRepository('AppBundle:Student')->findOneBy(
                            array('teacher' => $teacher, 'name' => $item['student'])
                          );
                            if (empty($student)) {
                                $failure = true;
                                $this->addFlash(
                                'warning',
                                  '[ROW #'.($i + 3).'] Could not add Offlinedonation. Student "'.$item['student'].'" not found'
                            );
                            }
                        }

                        if ($breakLoop) {
                            break;
                        }

                        if (!$failure) {
                            //Example: 2016-08-25 16:35:54
                            $logger->debug(print_r($item, true));
                            $date = new DateTime($item['date']);

                            $offlinedonation->setAmount($item['amount']);
                            $offlinedonation->setDonatedAt($date);
                            $offlinedonation->setStudent($student);

                            $validator = $this->get('validator');
                            $errors = $validator->validate($offlinedonation);

                            if (count($errors) > 0) {
                                /*
                                 * Uses a __toString method on the $errors variable which is a
                                 * ConstraintViolationList object. This gives us a nice string
                                 * for debugging.
                                 */
                                $errorsString = (string) $errors;
                                $this->addFlash('danger', '[ROW #'.($i + 4).'] Could not add offlinedonation for '.$item['student'].', error:'.$errorsString);
                            } else {
                                $em->persist($offlinedonation);
                                $em->flush();
                                $em->clear();
                            }
                        }
                    }

                    // flush the remaining objects
                    $em->flush();
                    $em->clear();

                    $this->addFlash(
                        'success',
                        'Completed'
                    );

                    return $this->redirectToRoute(strtolower($entity).'_index');
                } else {
                    $logger->info('file does not have mandatory fields. Please verify it was downloaded from Offline');
                    $logger->info('File was not a .csv');
                    $this->addFlash(
                        'danger',
                        'file does not have mandatory fields. Please verify it was downloaded from Offline'
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
