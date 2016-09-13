<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Causevoxteam;

/**
 * Causevoxteam controller.
 *
 * @Route("/manage/causevoxteam")
 */
class CausevoxteamController extends Controller
{
    /**
     * Lists all Causevoxteam entities.
     *
     * @Route("/", name="causevoxteam_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $entity = 'Causevoxteam';
        $em = $this->getDoctrine()->getManager();

        $causevoxteams = $em->getRepository('AppBundle:Causevoxteam')->findAll();

        return $this->render(strtolower($entity).'/index.html.twig', array(
            'causevoxteams' => $causevoxteams,
            'entity' => $entity,
        ));
    }

    /**
     * Creates a new Causevoxteam entity.
     *
     * @Route("/new", name="causevoxteam_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $entity = 'Causevoxteam';
        $causevoxteam = new Causevoxteam();
        $form = $this->createForm('AppBundle\Form\CausevoxteamType', $causevoxteam);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($causevoxteam);
            $em->flush();

            return $this->redirectToRoute(strtolower($entity).'_show', array('id' => $causevoxteam->getId()));
        }

        return $this->render('crud/new.html.twig', array(
            'causevoxteam' => $causevoxteam,
            'form' => $form->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Finds and displays a Causevoxteam entity.
     *
     * @Route("/show/{id}", name="causevoxteam_show")
     * @Method("GET")
     */
    public function showAction(Causevoxteam $causevoxteam)
    {
        $entity = 'Causevoxteam';
        $deleteForm = $this->createDeleteForm($causevoxteam);

        return $this->render(strtolower($entity).'/show.html.twig', array(
            'causevoxteam' => $causevoxteam,
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing Causevoxteam entity.
     *
     * @Route("/edit/{id}", name="causevoxteam_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Causevoxteam $causevoxteam)
    {
        $entity = 'Causevoxteam';
        $deleteForm = $this->createDeleteForm($causevoxteam);
        $editForm = $this->createForm('AppBundle\Form\CausevoxteamType', $causevoxteam);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($causevoxteam);
            $em->flush();

            return $this->redirectToRoute(strtolower($entity).'_edit', array('id' => $causevoxteam->getId()));
        }

        return $this->render('crud/edit.html.twig', array(
            'causevoxteam' => $causevoxteam,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Deletes a Causevoxteam entity.
     *
     * @Route("/delete/{id}", name="causevoxteam_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Causevoxteam $causevoxteam)
    {
        $entity = 'Causevoxteam';
        $form = $this->createDeleteForm($causevoxteam);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($causevoxteam);
            $em->flush();
        }

        return $this->redirectToRoute(strtolower($entity).'_index');
    }

    /**
     * Creates a form to delete a Causevoxteam entity.
     *
     * @param Causevoxteam $causevoxteam The Causevoxteam entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Causevoxteam $causevoxteam)
    {
        $entity = 'Causevoxteam';

        return $this->createFormBuilder()
            ->setAction($this->generateUrl(strtolower($entity).'_delete', array('id' => $causevoxteam->getId())))
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
        $entity = 'Causevoxteam';
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
                            'Causevoxteam table truncated'
                        );
                    }

                    $logger->info('Uploading Data');
                    $em = $this->getDoctrine()->getManager();
                    $batchSize = 20;

                    foreach ($fileData as $i => $item) {
                        $causevoxteam = new Causevoxteam();
                        $grade = $this->getDoctrine()->getRepository('AppBundle:Grade')->findOneByName($item['grade']);

                        if (empty($grade)) {
                            $this->addFlash(
                                'danger',
                                "Could not add Causevoxteam '".$item['name']."'. Grade '".$item['grade']."' not found"
                            );
                        } else {
                            $teacher = $this->getDoctrine()->getRepository('AppBundle:Teacher')->findOneBy(
                                array('teacherName' => $item['teachers_name'], 'grade' => $grade->getId())
                            );
                            if (empty($teacher)) {
                                $this->addFlash(
                                    'danger',
                                      "Could not add Causevoxteam '".$item['name']."'. Teacher '".$item['teachers_name']."' not found"
                                );
                            } else {
                                $causevoxteam->setName($item['name']);
                                $causevoxteam->setFundsNeeded($item['funds_needed']);
                                $causevoxteam->setUrl($item['url']);
                                $causevoxteam->setFundsRaised($item['funds_raised']);
                                $causevoxteam->setTeacher($teacher);
                                $em->persist($causevoxteam);

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
