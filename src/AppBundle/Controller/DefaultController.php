<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Utils\CampaignHelper;
use AppBundle\Entity\Teacher;
use AppBundle\Entity\Campaign;
use AppBundle\Utils\QueryHelper;

use DateTime;

class DefaultController extends Controller
{

  /**
   * @Route("/", name="homepage")
   */
  public function indexAction(Request $request)
  {

      $logger = $this->get('logger');
      $entity = 'Campaign';
      $em = $this->getDoctrine()->getManager();
      $campaigns = $em->getRepository('AppBundle:Campaign')->findAll();

      if(null !== $request->query->get('action') && $request->query->get('action') === 'new_campaign'){
          $action = $request->query->get('action');
          $campaign = new Campaign();
          $form = $this->createForm('AppBundle\Form\CampaignType');
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

              return $this->redirectToRoute('homepage', array('id' => $campaign->getId()));
          }

          return $this->render('campaign/campaign.new.html.twig', array(
              'form' => $form->createView(),
              'entity' => $entity,
          ));
      }

      return $this->render('campaign/campaign.index.html.twig', array(
          'campaigns' => $campaigns,
          'entity' => $entity,
      ));
  }

}
