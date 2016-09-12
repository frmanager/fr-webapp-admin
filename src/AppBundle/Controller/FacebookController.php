<?php

// src/AppBundle/Controller/FacebookController.php
namespace AppBundle\Controller;

// ...
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

// src/AppBundle/Controller/FacebookController.php

// ...
class FacebookController extends Controller
{
    /**
     * @Route("/facebook-tab")
     */
    public function facebookTab()
    {
        $url = 'http://funrun.lrespto.org';
        $campaign_start_date = '09/15/2016';
        $campaign_end_date = '10/27/2016';
        $campaign_funding_goal = '20000';

        $campaignStartDateString = strtotime($campaign_start_date);
        $campaignStartDate = date('Y-m-d', $campaignStartDateString);

        $campaignEndDateString = strtotime($campaign_end_date);
        $campaignEndDate = date('Y-m-d', $campaignEndDateString);

        $todaysDate = date('Y-m-d');

        $data = [
              'todays_date' => $todaysDate,
              'campaign_url' => $url,
              'campaign_start_date' => $campaignStartDate,
              'campaign_end_date' => $campaignEndDate,
            ];

        //TODO: Make sure that only data necessary is going to the particular views....I.E. File Data
        if ($todaysDate > $campaignEndDate) {
            $theView = 'closeout.phtml';
        } elseif ($todaysDate > $campaignStartDate) {
            $theView = 'track.phtml';
        } else {
            $theView = 'prepare.phtml';
        }

        return $this->render('facebook-tab/'.$theView.'.html.twig', array('data' => $data));
    }
}
