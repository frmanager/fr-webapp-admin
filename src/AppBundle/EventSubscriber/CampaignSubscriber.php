<?php

namespace AppBundle\EventSubscriber;

use AppBundle\Controller\TokenAuthenticatedController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use AppBundle\Entity\Campaign;


class CampaignSubscriber implements EventSubscriberInterface
{
    private $container;

    public function __construct($container)
    {
      $this->container = $container;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        /*
         * $controller passed can be either a class or a Closure.
         * This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controller)) {
            return;
        }


        /*
        $session = $this->container->get('session');

        if(!$this->container->get('session')->get('campaign')){
          // Get the doctrine service
          $doctrine_service = $this->container->get('doctrine');
          // Get the entity manager
          $em = $doctrine_service->getEntityManager();
          $campaign = $em->getRepository('AppBundle:Campaign')->findByUrl($this->container->get('session')->get('campaign'));
          //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
          if(is_null($campaign)){
            $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this campaign.');
            return $this->redirectToRoute('homepage');
          }

          //CODE TO PROTECT CONTROLLER FROM USERS WHO ARE NOT IN CAMPAIGNUSER TABLE
          //TODO: ADD CODE TO ALLOW ADMINS TO ACCESS
          $query = $em->createQuery('SELECT IDENTITY(cu.campaign) FROM AppBundle:CampaignUser cu where cu.user=?1');
          $query->setParameter(1, $this->get('security.token_storage')->getToken()->getUser());
          $results = array_map('current', $query->getScalarResult());
          if(!in_array($campaign->getId(), $results)){
            $this->get('session')->getFlashBag()->add('warning', 'You do not have permissions to this campaign.');
            return $this->redirectToRoute('homepage');
          }

          $session->save();
        }else{
          return;
        }
        */

    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => 'onKernelController',
        );
    }
}
