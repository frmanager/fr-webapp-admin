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
              $campaign = $em->getRepository('AppBundle:Campaign')->find(1);
              $this->container->get('session')->set('campaign', $campaign);
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
