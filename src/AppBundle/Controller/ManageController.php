<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Utils\CampaignHelper;
use AppBundle\Utils\QueryHelper;
use DateTime;

/**
 * Grade controller.
 *
 * @Route("/manage")
 */
class ManageController extends Controller
{
    /**
     * @Route("/", name="manage_index")
     */
    public function indexAction(Request $request)
    {
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();
        $queryHelper = new QueryHelper($em, $logger);
        $campaignSettings = new CampaignHelper($this->getDoctrine()->getRepository('AppBundle:Campaignsetting')->findAll());

        $tempDate = new DateTime();
        $dateString = $tempDate->format('Y-m-d').' 00:00:00';
        $todaysDate = DateTime::createFromFormat('Y-m-d H:i:s', $dateString);

        // replace this example code with whatever you need
        return $this->render('manage/index.html.twig', array(
          'campaign_settings' => $campaignSettings->getCampaignSettings(),
          'new_teacher_awards' => $queryHelper->getNewTeacherAwards(array('day_modifier' => '-1 day')),
          'teacher_rankings' => $queryHelper->getTeacherRanks(10),
          'student_rankings' => $queryHelper->getStudentRanks(10),
          'total_donation_amount' => $queryHelper->getTotalDonationAmount(),
          'total_number_of_donations' => $queryHelper->getTotalNumberOfDonations(),
        ));
    }

}
