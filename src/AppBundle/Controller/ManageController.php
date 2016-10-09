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

        // replace this example code with whatever you need
        return $this->render('manage/index.html.twig', array(
          'campaign_settings' => $campaignSettings->getCampaignSettings(),
          'new_teacher_awards' => $queryHelper->getTeacherAwards(array()),
          'teacher_rankings' => $queryHelper->getTeacherRanks(array('limit'=> 10)),
          'student_rankings' => $queryHelper->getStudentRanks(array('limit'=> 10)),
          'totals' => $queryHelper->getTotalDonations(array()),
        ));
    }

}
