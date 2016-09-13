<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Causevoxfundraiser;

/**
 * Causevoxteam controller.
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

        $causevoxteams = $em->getRepository('AppBundle:Causevoxfundraiser')->findAll();

        return $this->render(strtolower($entity).'/index.html.twig', array(
            'causevoxfundraiser' => $causevoxfundraiser,
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
     * Creates a form to delete a Causevoxteam entity.
     *
     * @param Causevoxfundraiser $causevoxfundraiser The Causevoxfundraiser entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Causevoxteam $causevoxteam)
    {
        $entity = 'Causevoxfundraiser';

        return $this->createFormBuilder()
            ->setAction($this->generateUrl(strtolower($entity).'_delete', array('id' => $causevoxfundraiser->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Upload multiple Causevoxteams via CSV File.
     *
     * @Route("/upload", name="causevoxteam_upload")
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
                          //$logger->info(print_r($fileLabels, true));
                      } else {
                          foreach ($thisRow as $key => $value) {
                              if (strcmp($fileLabels[$key], 'teachers_name') == 0) {
                                  $teacherNameString = substr(trim($value), strpos(trim($value), ' - ') + 3, strlen(trim($value)));
                                  //$logger->info('Teachers Name Old: "'.$value.'" vs new: "'.$teacherNameString.'"');
                                  $thisRowAsObjects[$fileLabels[$key]] = $teacherNameString;
                              } else {
                                  //$logger->info('Key: "'.$key.'" Value: "'.$value.'"');
                                  $thisRowAsObjects[$fileLabels[$key]] = trim($value);
                              }
                          }
                          array_push($fileData, $thisRowAsObjects);
                      }
                  }
                    ++$counter;
                }
                fclose($csvFile);
                unlink('temp/'.strtolower($entity).'.csv');
                $logger->info(print_r($fileLabels, true));
                if (in_array('name', $fileLabels) && in_array('grade', $fileLabels) && in_array('url', $fileLabels) && in_array('funds_needed', $fileLabels) && in_array('funds_raised', $fileLabels) && in_array('teachers_name', $fileLabels)) {
                    $logger->info('Making changes to database');
                    $em = $this->getDoctrine()->getManager();

                    if ($truncateFlag) {
                        $logger->info('Clearing Table.');

                        $qb = $em->createQueryBuilder();
                        $qb->delete('AppBundle:Causevoxteam', 's');
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

                    foreach ($fileData as $i => $item) {
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
                                $causevoxfundraiser->setName($item['name']);
                                $causevoxfundraiser->setFundsNeeded($item['funds_needed']);
                                $causevoxfundraiser->setUrl($item['url']);
                                $causevoxfundraiser->setFundsRaised($item['funds_raised']);
                                $causevoxfundraiser->setTeacher($teacher);
                                $em->persist($causevoxfundraiser);

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

    private function clean($string)
    {
        $string = str_replace(' ', '_', $string); // Replaces all spaces with underscores.
        $string = preg_replace('/[^A-Za-z0-9\_]/', '', $string); // Removes special chars.
        $string = trim($string);

        return strtolower($string);
    }
}
