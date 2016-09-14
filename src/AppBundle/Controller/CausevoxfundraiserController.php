<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Causevoxfundraiser;
use AppBundle\Entity\Grade;
use AppBundle\Entity\Student;
use AppBundle\Utils\CSVHelper;

/**
 * Causevoxfundraiser controller.
 *
 * @Route("/manage/causevoxfundraiser")
 */
class CausevoxfundraiserController extends Controller
{
    /**
     * Lists all Causevoxfundraiser entities.
     *
     * @Route("/", name="causevoxfundraiser_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $entity = 'Causevoxfundraiser';
        $em = $this->getDoctrine()->getManager();

        $causevoxfundraisers = $em->getRepository('AppBundle:Causevoxfundraiser')->findAll();

        return $this->render(strtolower($entity).'/index.html.twig', array(
            'causevoxfundraisers' => $causevoxfundraisers,
            'entity' => $entity,
        ));
    }

    /**
     * Creates a new Causevoxfundraiser entity.
     *
     * @Route("/new", name="causevoxfundraiser_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $entity = 'Causevoxfundraiser';
        $causevoxfundraiser = new Causevoxfundraiser();
        $form = $this->createForm('AppBundle\Form\CausevoxfundraiserType', $causevoxfundraiser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($causevoxfundraiser);
            $em->flush();

            return $this->redirectToRoute(strtolower($entity).'_show', array('id' => $causevoxfundraiser->getId()));
        }

        return $this->render('crud/new.html.twig', array(
            'causevoxfundraiser' => $causevoxfundraiser,
            'form' => $form->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Finds and displays a Causevoxfundraiser entity.
     *
     * @Route("/show/{id}", name="causevoxfundraiser_show")
     * @Method("GET")
     */
    public function showAction(Causevoxfundraiser $causevoxfundraiser)
    {
        $entity = 'Causevoxfundraiser';
        $deleteForm = $this->createDeleteForm($causevoxfundraiser);

        return $this->render(strtolower($entity).'/show.html.twig', array(
            'causevoxfundraiser' => $causevoxfundraiser,
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing Causevoxfundraiser entity.
     *
     * @Route("/edit/{id}", name="causevoxfundraiser_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Causevoxfundraiser $causevoxfundraiser)
    {
        $entity = 'Causevoxfundraiser';
        $deleteForm = $this->createDeleteForm($causevoxfundraiser);
        $editForm = $this->createForm('AppBundle\Form\CausevoxfundraiserType', $causevoxfundraiser);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($causevoxfundraiser);
            $em->flush();

            return $this->redirectToRoute(strtolower($entity).'_edit', array('id' => $causevoxfundraiser->getId()));
        }

        return $this->render('crud/edit.html.twig', array(
            'causevoxfundraiser' => $causevoxfundraiser,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Deletes a Causevoxfundraiser entity.
     *
     * @Route("/delete/{id}", name="causevoxfundraiser_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Causevoxfundraiser $causevoxfundraiser)
    {
        $entity = 'Causevoxfundraiser';
        $form = $this->createDeleteForm($causevoxfundraiser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($causevoxfundraiser);
            $em->flush();
        }

        return $this->redirectToRoute(strtolower($entity).'_index');
    }

    /**
     * Creates a form to delete a Causevoxfundraiser entity.
     *
     * @param Causevoxfundraiser $causevoxfundraiser The Causevoxfundraiser entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Causevoxfundraiser $causevoxfundraiser)
    {
        $entity = 'Causevoxfundraiser';

        return $this->createFormBuilder()
            ->setAction($this->generateUrl(strtolower($entity).'_delete', array('id' => $causevoxfundraiser->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Upload multiple Causevoxfundraiser via CSV File.
     *
     * @Route("/upload", name="causevoxfundraiser_upload")
     * @Method({"GET", "POST"})
     */
    public function uploadForm(Request $request)
    {
        $logger = $this->get('logger');
        $entity = 'Causevoxfundraiser';
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
                $csvHelper->processFile('temp/', strtolower($entity).'.csv');
                $csvHelper->cleanTeacherNames();

                $logger->info(print_r($csvHelper->getData(), true));

                $templateFields = array('stub', 'first_name', 'last_name', 'email', 'funds_raised', 'funds_needed', 'numbe_rof_donations', 'teams', 'joined', 'grade', 'teachers_name', 'student_name');

                if ($csvHelper->validateHeaders($templateFields)) {
                    $logger->info('Making changes to database');
                    $em = $this->getDoctrine()->getManager();

                    if ($truncateFlag) {
                        $logger->info('Clearing Table.');

                        $qb = $em->createQueryBuilder();
                        $qb->delete('AppBundle:Causevoxfundraiser', 's');
                        $query = $qb->getQuery();

                        if ($query->getResult() == 0) {
                            $logger->info('Something Happened');
                        }
                        $em->flush();

                        $this->addFlash(
                            'info',
                            'Causevoxfundraiser table truncated'
                        );
                    }

                    $logger->info('Uploading Data');
                    $em = $this->getDoctrine()->getManager();
                    $batchSize = 20;

                    foreach ($csvHelper->getData() as $i => $item) {
                        $causevoxfundraiser = new Causevoxfundraiser();
                        $grade = $this->getDoctrine()->getRepository('AppBundle:Grade')->findOneByName($item['grade']);
                        if (empty($grade)) {
                            $this->addFlash(
                                'danger',
                                "Could not add Causevoxfundraiser '".$item['name']."'. Grade '".$item['grade']."' not found"
                            );
                        } else {
                            $teacher = $this->getDoctrine()->getRepository('AppBundle:Teacher')->findOneBy(
                                array('teacherName' => $item['teachers_name'], 'grade' => $grade->getId())
                            );
                            if (empty($teacher)) {
                                $this->addFlash(
                                    'danger',
                                      "Could not add Causevoxfundraiser '".$item['name']."'. Teacher '".$item['teachers_name']."' not found"
                                );
                            } else {
                                $student = $this->getDoctrine()->getRepository('AppBundle:Student')->findOneBy(
                                  array('teacher' => $teacher->getId(), 'name' => $item['students_name'])
                                );
                                if (empty($student)) {
                                    $this->addFlash(
                                      'danger',
                                        "Could not add Causevoxfundraiser '".$item['first_name'].' '.$item['last_name']."'. Student '".$item['students_name']."' not found"
                                  );
                                } else {
                                    $causevoxfundraiser->setEmail($item['email']);
                                    $causevoxfundraiser->setFundsNeeded($item['funds_needed']);
                                    $causevoxfundraiser->setUrl($item['stub']);
                                    $causevoxfundraiser->setFundsRaised($item['funds_raised']);
                                    $causevoxfundraiser->setStudent($student);

                                    $em->persist($causevoxfundraiser);

                                     // flush everything to the database every 20 inserts
                                     if (($i % $batchSize) == 0) {
                                         $em->flush();
                                         $em->clear();
                                     }
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
