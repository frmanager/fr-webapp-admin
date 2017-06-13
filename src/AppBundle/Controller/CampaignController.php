<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Utils\CampaignHelper;
use AppBundle\Entity\Teacher;
use AppBundle\Utils\QueryHelper;

use DateTime;

/**
 * Manage Grade controller.
 *
 * @Route("/{campaignUrl}")
 */
class CampaignController extends Controller
{

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

      return $this->render('campaign/show.html.twig', array(
          'campaign' => $campaign,
          'delete_form' => $deleteForm->createView(),
          'entity' => $entity,
      ));
  }

  /**
   * Displays a form to edit an existing Campaign entity.
   *
   * @Route("/edit", name="campaign_edit")
   * @Method({"GET", "POST"})
   */
  public function editAction(Request $request, $campaignUrl)
  {
      $em = $this->getDoctrine()->getManager();
      $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
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

          return $this->redirectToRoute('campaign_dashboard', array('campaignUrl'=> $campaignUrl));
      }

      return $this->render('crud/manage.edit.html.twig', array(
          'campaign' => $campaign,
          'edit_form' => $editForm->createView(),
          'delete_form' => $deleteForm->createView(),
          'entity' => $entity,
      ));
  }

  /**
   * Deletes a Campaign entity.
   *
   * @Route("/delete", name="campaign_delete")
   * @Method("DELETE")
   */
  public function deleteAction(Request $request, $campaign)
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

      return $this->redirectToRoute('homepage');
  }

  /**
   * Creates a form to delete a Campaign entity.
   *
   * @param Campaign $campaign The Campaign entity
   *
   * @return \Symfony\Component\Form\Form The form
   */
  private function createDeleteForm($campaign)
  {
      $entity = 'Campaign';
      return $this->createFormBuilder()
          ->setAction($this->generateUrl('campaign_delete', array('campaignUrl' => $campaign->getUrl())))
          ->setMethod('DELETE')
          ->getForm()
      ;
  }


}
