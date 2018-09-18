<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use App\Utils\CampaignHelper;
use App\Entity\Teacher;
use App\Entity\Campaign;
use App\Utils\QueryHelper;
use Doctrine\ORM\Query\Expr;

use DateTime;

class DefaultController extends Controller
{

  /**
   * @Route("/", name="homepage")
   */
  public function indexAction(Request $request, LoggerInterface $logger)
  {

      $entity = 'Campaign';

      $em = $this->getDoctrine()->getManager();

      if(null !== $request->query->get('action') && $request->query->get('action') === 'new_campaign'){
          $action = $request->query->get('action');
          $campaign = new Campaign();
          $form = $this->createForm('App\Form\CampaignType');
          $form->handleRequest($request);

          $logger->debug("Logged in User ID: ".$this->getUser()->getId());
          $user = $this->getDoctrine()->getRepository('App:User')->find($this->getUser()->getId());
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


      $qb = $em->createQueryBuilder()->select('c')
           ->from('App:Campaign', 'c')
           ->join('App:CampaignUser', 'cu')
           ->where('cu.campaign = c.id')
           ->andWhere('cu.user = :user')
           ->setParameter('user', $this->get('security.token_storage')->getToken()->getUser());

      $campaigns = $qb->getQuery()->getResult();

      return $this->render('campaign/campaign.index.html.twig', array(
          'campaigns' => $campaigns,
          'entity' => $entity,
      ));
  }

}
