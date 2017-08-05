<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Campaignaward;
use AppBundle\Entity\Campaign;
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
     * @Route("/", name="campaignaward_index")
     * @Method("GET")
     */
    public function indexAction($campaignUrl)
    {
        $entity = 'Campaignaward';
        $em = $this->getDoctrine()->getManager();

        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
        if(is_null($campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this campaign.');
          return $this->redirectToRoute('homepage');
        }

        if(!$this->campaignPermissionsCheck($campaign)){
            $this->get('session')->getFlashBag()->add('warning', 'You do not have permissions to this campaign.');
            return $this->redirectToRoute('homepage');
        }

        $campaignawardtypes = $em->getRepository('AppBundle:Campaignawardtype')->findAll();
        if (empty($campaignawardtypes)) {
            $defaultCampaignawardtypes = [];

            array_push($defaultCampaignawardtypes, array('name' => 'Teacher/Class', 'value' => 'teacher', 'description' => ''));
            array_push($defaultCampaignawardtypes, array('name' => 'Student/Individual', 'value' => 'student', 'description' => ''));

            foreach ($defaultCampaignawardtypes as $defaultCampaignawardtype) {
                $em = $this->getDoctrine()->getManager();

                $campaignawardtype = new Campaignawardtype();
                $campaignawardtype->setDisplayName($defaultCampaignawardtype['name']);
                $campaignawardtype->setValue($defaultCampaignawardtype['value']);
                $campaignawardtype->setDescription($defaultCampaignawardtype['description']);

                $em->persist($campaignawardtype);
                $em->flush();
            }
        }

        $campaignawardstyles = $em->getRepository('AppBundle:Campaignawardstyle')->findAll();
        if (empty($campaignawardstyles)) {
            $defaultCampaignawardstyles = [];

            array_push($defaultCampaignawardstyles, array('name' => 'Place', 'value' => 'place', 'description' => ''));
            array_push($defaultCampaignawardstyles, array('name' => 'Donation Level', 'value' => 'level', 'description' => 'award received if (Teacher/Student) reach donation amount'));

            foreach ($defaultCampaignawardstyles as $defaultCampaignawardstyle) {
                $em = $this->getDoctrine()->getManager();

                $campaignawardstyle = new Campaignawardstyle();
                $campaignawardstyle->setDisplayName($defaultCampaignawardstyle['name']);
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
     * @Route("/new", name="campaignaward_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, $campaignUrl)
    {
        $logger = $this->get('logger');
        $entity = 'Campaignaward';

        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
        if(is_null($campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this campaign.');
          return $this->redirectToRoute('homepage');
        }

        if(!$this->campaignPermissionsCheck($campaign)){
            $this->get('session')->getFlashBag()->add('warning', 'You do not have permissions to this campaign.');
            return $this->redirectToRoute('homepage');
        }

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
     * @Route("/{id}", name="campaignaward_show")
     * @Method("GET")
     */
    public function showAction(Campaignaward $campaignaward, $campaignUrl)
    {
        $entity = 'Campaignaward';
        $em = $this->getDoctrine()->getManager();

        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
        if(is_null($campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this campaign.');
          return $this->redirectToRoute('homepage');
        }

        if(!$this->campaignPermissionsCheck($campaign)){
            $this->get('session')->getFlashBag()->add('warning', 'You do not have permissions to this campaign.');
            return $this->redirectToRoute('homepage');
        }


        $deleteForm = $this->createDeleteForm($campaignaward, $campaignUrl);

        return $this->render('campaign/campaignAward.show.html.twig', array(
            'campaignaward' => $campaignaward,
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
            'campaign' => $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl)
        ));
    }

    /**
     * Displays a form to edit an existing Campaignaward entity.
     *
     * @Route("/edit/{id}", name="campaignaward_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Campaignaward $campaignaward, $campaignUrl)
    {
        $logger = $this->get('logger');
        $entity = 'Campaignaward';
        $em = $this->getDoctrine()->getManager();

        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
        if(is_null($campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this campaign.');
          return $this->redirectToRoute('homepage');
        }

        if(!$this->campaignPermissionsCheck($campaign)){
            $this->get('session')->getFlashBag()->add('warning', 'You do not have permissions to this campaign.');
            return $this->redirectToRoute('homepage');
        }


        $deleteForm = $this->createDeleteForm($campaignaward, $campaignUrl);
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
     * @Route("/delete/{id}", name="campaignaward_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Campaignaward $campaignaward, $campaignUrl)
    {
        $entity = 'Campaignaward';

        $em = $this->getDoctrine()->getManager();

        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
        if(is_null($campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this campaign.');
          return $this->redirectToRoute('homepage');
        }

        if(!$this->campaignPermissionsCheck($campaign)){
            $this->get('session')->getFlashBag()->add('warning', 'You do not have permissions to this campaign.');
            return $this->redirectToRoute('homepage');
        }

        $form = $this->createDeleteForm($campaignaward, $campaignUrl);
        $form->handleRequest($request);
        $em = $this->getDoctrine()->getManager();
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($campaignaward);
            $em->flush();
        }

        return $this->redirectToRoute('campaignaward_index');
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

    /**
    *
    * campaignPermissionsCheck takes the campaign that was requested and verifies user has access to it
    *
    * Access is verified by looking at the CampaignUser entity and verifying a record exists
    * for that campaign and user combination
    *
    * @param Campaign $campaign
    *
    * @return boolean
    *
    */
    private function campaignPermissionsCheck(Campaign $campaign){
      $em = $this->getDoctrine()->getManager();

      //CODE TO PROTECT CONTROLLER FROM USERS WHO ARE NOT IN CAMPAIGNUSER TABLE
      //TODO: ADD CODE TO ALLOW ADMINS TO ACCESS
      $query = $em->createQuery('SELECT IDENTITY(cu.campaign) FROM AppBundle:CampaignUser cu where cu.user=?1');
      $query->setParameter(1, $this->get('security.token_storage')->getToken()->getUser());
      $results = array_map('current', $query->getScalarResult());
      if(!in_array($campaign->getId(), $results)){
        return false;
      }
      return true;
    }
}
