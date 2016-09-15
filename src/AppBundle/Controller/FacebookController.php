<?php

// src/AppBundle/Controller/FacebookController.php
namespace AppBundle\Controller;

// ...
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use DateTime;

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
        $url = 'http://funrun.lrespto.org';
        $campaign_start_date = '09/15/2016';
        $campaign_end_date = '10/27/2016';
        $campaign_funding_goal = '20000';

        $campaignStartDateString = strtotime($campaign_start_date);
        $campaignStartDate = date('Y-m-d', $campaignStartDateString);

        $campaignEndDateString = strtotime($campaign_end_date);
        $campaignEndDate = date('Y-m-d', $campaignEndDateString);

        $campaignEndDateCountdown = date('Y/m/d', $campaignEndDateString);

        //$campaignStartDate = new DateTime('09/15/2016T00:00:00-05:00Z');
        //$campaignEndDate = new DateTime('10/27/2016T00:00:00-05:00Z');

        $todaysDate = date('Y-m-d');

        $data = [
              'todays_date' => $todaysDate,
              'campaign_url' => $url,
              'campaign_start_date' => $campaignStartDate,
              'campaign_end_date' => $campaignEndDate,
              'campaign_end_date_countdown' => $campaignEndDateCountdown,
              'campaign_funding_goal' => $campaign_funding_goal,
            ];

        //TODO: Make sure that only data necessary is going to the particular views....I.E. File Data

        if ($todaysDate >= $campaignEndDate) {
            $theView = 'closeout';
        } elseif ($todaysDate >= $campaignStartDate) {
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
