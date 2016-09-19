<?php

// src/AppBundle/Controller/FacebookController.php
namespace AppBundle\Controller;

// ...
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Campaignsetting;

// src/AppBundle/Controller/FacebookController.php

/**
 * Facebook controller.
 *
 * @Route("/facebook")
 */
class FacebookController extends Controller
{
    /**
     * @Route("/tab", name="facebook-tab")
     */
    public function facebookTab()
    {
        $logger = $this->get('logger');
        $data = [];

        $em = $this->getDoctrine()->getManager();
        $campaignsettings = $em->getRepository('AppBundle:Campaignsetting')->findAll();

        foreach ($campaignsettings as $campaignsetting) {
            $name = $campaignsetting->getDisplayName();
            $value = $campaignsetting->getValue();
            if (strcmp($name, 'campaign_start_date') == 0) {
                $data['campaign_start_date'] = date('Y-m-d', strtotime($value));
            } elseif (strcmp($name, 'campaign_end_date') == 0) {
                $data['campaign_end_date'] = date('Y-m-d', strtotime($value));
                $data['campaign_end_date_countdown'] = date('Y/m/d', strtotime($value));
            } elseif (strcmp($name, 'campaign_funding_goal') == 0) {
                $data['campaign_funding_goal'] = (float) $value;
            } elseif (strcmp($name, 'campaign_url') == 0) {
                $data['campaign_url'] = $value;
            }
        }

        if (date('Y-m-d') >= $data['campaign_end_date']) {
            $theView = 'closeout';
        } elseif (date('Y-m-d') >= $data['campaign_start_date']) {
            $em = $this->getDoctrine()->getManager();
            $causevoxteams = $em->getRepository('AppBundle:Causevoxteam')->findAll();

            $qb = $em->createQueryBuilder();
            $qb->select(array('SUM(u.amount) as total'))->from('AppBundle:Causevoxdonation', 'u');
            $query = $qb->getQuery();
            $result = $query->getResult();
            $logger->debug('Total Donations from Causevoxe: '.$result[0]['total']);
            $data['total_donations'] = $result[0]['total'];

            $data['causevoxteams'] = $causevoxteams;
            $theView = 'track';
        } else {
            $theView = 'prepare';
        }

        return $this->render('facebook-tab/'.$theView.'.html.twig', array('data' => $data));
    }
}
