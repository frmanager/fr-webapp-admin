<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Campaign;

/**
 * Campaign controller.
 *
 * @Route("/campaign")
 */
class CampaignController extends Controller
{
    /**
     * Lists all Campaign entities.
     *
     * @Route("/", name="campaign_index")
     * @Method("GET")
     */
     public function indexAction()
     {
         $logger = $this->get('logger');
         $entity = 'Campaign';
         $em = $this->getDoctrine()->getManager();

         $campaigns = $em->getRepository('AppBundle:Campaign')->findAll();

         return $this->render(strtolower($entity).'/index.html.twig', array(
             'campaigns' => $campaigns,
             'entity' => $entity,
         ));
     }



     /**
      * Set Campaign entities.
      *
      * @Route("/set/{id}", name="campaign_set")
      * @Method("GET")
      */
      public function setAction(Request $request, Campaign $campaign)
      {
          $logger = $this->get('logger');
          $entity = 'Campaign';
          if($this->container->get('session')->isStarted()){
            $logger->debug("THERE IS A SESSION");
          }
          $this->get('session')->set('campaign', $campaign);

          return $this->redirectToRoute('homepage');
      }


    /**
     * Creates a new Campaign entity.
     *
     * @Route("/new", name="campaign_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $logger = $this->get('logger');
        $entity = 'Campaign';
        $campaign = new Campaign();
        $form = $this->createForm('AppBundle\Form\CampaignType', $campaign);
        $form->handleRequest($request);

        $logger->debug("Logged in User ID: ".$this->getUser()->getId());
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->find($this->getUser()->getId());
        $logger->debug("User ID: ".$user->getId());

        //TODO: Add custom validation
        if ($form->isSubmitted()) {
            $logger->debug("Creating new Campaign");
            $em = $this->getDoctrine()->getManager();
            $em->persist($campaign);
            $em->flush();

            return $this->redirectToRoute('campaign_index', array('id' => $campaign->getId()));
        }

        return $this->render('crud/new.html.twig', array(
            'campaign' => $campaign,
            'form' => $form->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Finds and displays a Campaign entity.
     *
     * @Route("/show/{id}", name="campaign_show")
     * @Method("GET")
     */
    public function showAction(Campaign $campaign)
    {
        $logger = $this->get('logger');
        $entity = 'Campaign';
        $deleteForm = $this->createDeleteForm($campaign);

        return $this->render(strtolower($entity).'/show.html.twig', array(
            'campaign' => $campaign,
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing Campaign entity.
     *
     * @Route("/edit/{id}", name="campaign_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Campaign $campaign)
    {
        $logger = $this->get('logger');
        $entity = 'Campaign';
        $deleteForm = $this->createDeleteForm($campaign);
        $editForm = $this->createForm('AppBundle\Form\CampaignType', $campaign);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($campaign);
            $em->flush();

            $this->addFlash(
              'success',
              'Campaigns Saved!'
            );

            return $this->redirectToRoute('campaign_index', array('id' => $campaign->getId()));
        }

        return $this->render('crud/edit.html.twig', array(
            'campaign' => $campaign,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Deletes a Campaign entity.
     *
     * @Route("/delete/{id}", name="campaign_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Campaign $campaign)
    {
        $logger = $this->get('logger');
        $entity = 'Campaign';
        $form = $this->createDeleteForm($campaign);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($campaign);
            $em->flush();
        }

        return $this->redirectToRoute('campaign_index');
    }

    /**
     * Creates a form to delete a Campaign entity.
     *
     * @param Campaign $campaign The Campaign entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Campaign $campaign)
    {
        $entity = 'Campaign';
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('campaign_delete', array('id' => $campaign->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
