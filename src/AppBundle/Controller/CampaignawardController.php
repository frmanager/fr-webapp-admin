<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Campaignaward;
use AppBundle\Entity\Campaignawardtype;
use AppBundle\Entity\Campaignawardstyle;

/**
 * Campaignaward controller.
 *
 * @Route("/{campaignUrl}/awards")
 */
class CampaignawardController extends Controller
{
    /**
     * Lists all Campaignaward entities.
     *
     * @Route("/", name="manageCampaignaward_index")
     * @Method("GET")
     */
    public function indexAction($campaignUrl)
    {
        $entity = 'Campaignaward';
        $em = $this->getDoctrine()->getManager();
        $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);

        $campaignawardtypes = $em->getRepository('AppBundle:Campaignawardtype')->findAll();
        if (empty($campaignawardtypes)) {
            $defaultCampaignawardtypes = [];

            array_push($defaultCampaignawardtypes, array('displayName' => 'Teacher/Class', 'value' => 'teacher', 'description' => ''));
            array_push($defaultCampaignawardtypes, array('displayName' => 'Student/Individual', 'value' => 'student', 'description' => ''));

            foreach ($defaultCampaignawardtypes as $defaultCampaignawardtype) {
                $em = $this->getDoctrine()->getManager();

                $campaignawardtype = new Campaignawardtype();
                $campaignawardtype->setDisplayName($defaultCampaignawardtype['displayName']);
                $campaignawardtype->setValue($defaultCampaignawardtype['value']);
                $campaignawardtype->setDescription($defaultCampaignawardtype['description']);

                $em->persist($campaignawardtype);
                $em->flush();
            }
        }

        $campaignawardstyles = $em->getRepository('AppBundle:Campaignawardstyle')->findAll();
        if (empty($campaignawardstyles)) {
            $defaultCampaignawardstyles = [];

            array_push($defaultCampaignawardstyles, array('displayName' => 'Place', 'value' => 'place', 'description' => ''));
            array_push($defaultCampaignawardstyles, array('displayName' => 'Donation Level', 'value' => 'level', 'description' => 'award received if (Teacher/Student) reach donation amount'));

            foreach ($defaultCampaignawardstyles as $defaultCampaignawardstyle) {
                $em = $this->getDoctrine()->getManager();

                $campaignawardstyle = new Campaignawardstyle();
                $campaignawardstyle->setDisplayName($defaultCampaignawardstyle['displayName']);
                $campaignawardstyle->setValue($defaultCampaignawardstyle['value']);
                $campaignawardstyle->setDescription($defaultCampaignawardstyle['description']);

                $em->persist($campaignawardstyle);
                $em->flush();
            }
        }

        $em->clear();

        $campaignawards = $em->getRepository('AppBundle:'.$entity)->findByCampaign($campaign);

        return $this->render('campaign/campaignAward.index.html.twig', array(
            'campaignawards' => $campaignawards,
            'entity' => $entity,
            'campaign' => $campaign,
        ));
    }

    /**
     * Creates a new Campaignaward entity.
     *
     * @Route("/new", name="manageCampaignaward_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, $campaignUrl)
    {
        $logger = $this->get('logger');
        $entity = 'Campaignaward';
        $campaignaward = new Campaignaward();
        $form = $this->createForm('AppBundle\Form\CampaignawardType', $campaignaward);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $failure = false;
            $em = $this->getDoctrine()->getManager();

            if (strcmp($campaignaward->getCampaignawardstyle()->getValue(), 'level') == 0) {
                if (null == $campaignaward->getAmount()) {
                    $failure = true;
                    $this->addFlash(
                    'danger',
                    'If '.$campaignaward->getCampaignawardstyle()->getDisplayName().' is selected, you must have an associated amount'
                );
                } else {
                    if ($campaignaward->getAmount() < 0.01) {
                        $failure = true;
                        $this->addFlash(
                      'danger',
                      'Amount must be greater than $0.01'
                  );
                    }
                    $campaignaward->setPlace(null);
                }
            }

            if (strcmp($campaignaward->getCampaignawardstyle()->getValue(), 'place') == 0) {
                if (null == $campaignaward->getPlace()) {
                    $failure = true;
                    $this->addFlash(
                    'danger',
                    'If '.$campaignaward->getCampaignawardstyle()->getDisplayName().' is selected, you must have an associated place'
                );
                } else {
                    if ($campaignaward->getPlace() < 1) {
                        $failure = true;
                        $this->addFlash(
                      'danger',
                      'Place must be greater than 0'
                  );
                    }
                    $campaignaward->setAmount(null);
                }
            }

            if (!$failure) {
                $validator = $this->get('validator');
                $errors = $validator->validate($campaignaward);
                if (count($errors) > 0) {
                    $failure = true;
                    $errorsString = (string) $errors;
                    $logger->error('Could not update ['.$entity.']: '.$errorsString);
                    $this->addFlash(
                  'danger',
                  'Could not update ['.$entity.']: '.$errorsString
              );
                }
            }

            if (!$failure) {
                $campaignawardCheck = $this->getDoctrine()->getRepository('AppBundle:'.$entity)->findOneBy(
          array('campaignawardtype' => $campaignaward->getCampaignawardtype(), 'campaignawardstyle' => $campaignaward->getCampaignawardstyle(), 'amount' => $campaignaward->getAmount(), 'place' => $campaignaward->getPlace())
          );
                if (!empty($campaignawardCheck) && $campaignawardCheck->getId() !== $campaignaward->getId()) {
                    $failure = true;
                    $this->addFlash(
                            'danger',
                            'This combination for an award [Type/Style/Place/Amount] already exists'
                        );
                }
            }

            if (!$failure) {
                $em->persist($campaignaward);
                $em->flush();

                return $this->redirectToRoute('campaignaward_index', array('campaignUrl'=> $campaignUrl, 'id' => $campaignaward->getId()));
            }
        }

        return $this->render('crud/new.html.twig', array(
            'campaignaward' => $campaignaward,
            'form' => $form->createView(),
            'entity' => $entity,
            'campaign' => $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl)
        ));
    }

    /**
     * Finds and displays a Campaignaward entity.
     *
     * @Route("/{id}", name="manageCampaignaward_show")
     * @Method("GET")
     */
    public function showAction(Campaignaward $campaignaward, $campaignUrl)
    {
        $entity = 'Campaignaward';
        $deleteForm = $this->createDeleteForm($campaignaward, $campaignUrl);

        return $this->render(strtolower($entity).'campaign/campaignAward.show.html.twig', array(
            'campaignaward' => $campaignaward,
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
            'campaign' => $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl)
        ));
    }

    /**
     * Displays a form to edit an existing Campaignaward entity.
     *
     * @Route("/edit/{id}", name="manageCampaignaward_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Campaignaward $campaignaward, $campaignUrl)
    {
        $logger = $this->get('logger');
        $entity = 'Campaignaward';
        $deleteForm = $this->createDeleteForm($campaignaward);
        $editForm = $this->createForm('AppBundle\Form\CampaignawardType', $campaignaward);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $failure = false;
            if (strcmp($campaignaward->getCampaignawardstyle()->getValue(), 'level') == 0) {
                if (null == $campaignaward->getAmount()) {
                    $failure = true;
                    $this->addFlash(
                    'danger',
                    'If '.$campaignaward->getCampaignawardstyle()->getDisplayName().' is selected, you must have an associated amount'
                );
                } else {
                    if ($campaignaward->getAmount() < 0.01) {
                        $failure = true;
                        $this->addFlash(
                      'danger',
                      'Amount must be greater than $0.01'
                  );
                    }
                    $campaignaward->setPlace(null);
                }
            }

            if (strcmp($campaignaward->getCampaignawardstyle()->getValue(), 'place') == 0) {
                if (null == $campaignaward->getPlace()) {
                    $failure = true;
                    $this->addFlash(
                    'danger',
                    'If '.$campaignaward->getCampaignawardstyle()->getDisplayName().' is selected, you must have an associated place'
                );
                } else {
                    if ($campaignaward->getPlace() < 1) {
                        $failure = true;
                        $this->addFlash(
                      'danger',
                      'Place must be greater than 0'
                  );
                    }

                    $campaignaward->setAmount(null);
                }
            }

            if (!$failure) {
                $validator = $this->get('validator');
                $errors = $validator->validate($campaignaward);
                if (count($errors) > 0) {
                    $failure = true;
                    $errorsString = (string) $errors;
                    $logger->error('Could not update ['.$entity.']: '.$errorsString);
                    $this->addFlash(
                  'danger',
                  'Could not update ['.$entity.']: '.$errorsString
              );
                }
            }

            if (!$failure) {
                $campaignawardCheck = $this->getDoctrine()->getRepository('AppBundle:'.$entity)->findOneBy(
          array('campaignawardtype' => $campaignaward->getCampaignawardtype(), 'campaignawardstyle' => $campaignaward->getCampaignawardstyle(), 'amount' => $campaignaward->getAmount(), 'place' => $campaignaward->getPlace())
          );
                if (!empty($campaignawardCheck) && $campaignawardCheck->getId() !== $campaignaward->getId()) {
                    $failure = true;
                    $this->addFlash(
                            'danger',
                            'This combination for an award [Type/Style/Place/Amount] already exists'
                        );
                }
            }

            if (!$failure) {
                $em->persist($campaignaward);
                $em->flush();

                return $this->redirectToRoute('campaignaward_index', array('campaignUrl'=> $campaignUrl, 'id' => $campaignaward->getId()));
            }
        }

        return $this->render('crud/edit.html.twig', array(
            'campaignaward' => $campaignaward,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
            'campaign' => $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl)
        ));
    }

    /**
     * Deletes a Campaignaward entity.
     *
     * @Route("/delete/{id}", name="manageCampaignaward_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Campaignaward $campaignaward, $campaignUrl)
    {
        $entity = 'Campaignaward';
        $form = $this->createDeleteForm($campaignaward, $campaignUrl);
        $form->handleRequest($request);
        $em = $this->getDoctrine()->getManager();
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($campaignaward);
            $em->flush();
        }

        return $this->redirectToRoute('manageCampaignaward_index');
    }

    /**
     * Creates a form to delete a Campaignaward entity.
     *
     * @param Campaignaward $campaignaward The Campaignaward entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Campaignaward $campaignaward, $campaignUrl)
    {
        $entity = 'Campaignaward';

        return $this->createFormBuilder()
            ->setAction($this->generateUrl('campaignaward_delete', array('campaignUrl'=> $campaignUrl, 'id' => $campaignaward->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
