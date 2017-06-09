<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Campaign;
use AppBundle\Utils\CampaignHelper;
use AppBundle\Entity\Teacher;
use AppBundle\Utils\QueryHelper;
use DateTime;

/**
 * Campaign controller.
 *
 * @Route("/c")
 */
class CampaignController extends Controller
{
    /**
     * Lists all Campaign entities.
     *
     * @Route("/", name="campaign_index")
     * @Method("GET")
     */
     public function indexAction()
     {
         $logger = $this->get('logger');
         $entity = 'Campaign';
         $em = $this->getDoctrine()->getManager();

         return $this->redirectToRoute('homepage');

     }



       /**
        * Lists all Campaign entities.
        *
        * @Route("/{url}", name="campaign_show")
        * @Method("GET")
        */
        public function showAction($url)
        {
          $logger = $this->get('logger');
          $limit = 3;
          $em = $this->getDoctrine()->getManager();
          $queryHelper = new QueryHelper($em, $logger);
          $campaignSettings = new CampaignHelper($this->getDoctrine()->getRepository('AppBundle:Campaignsetting')->findAll());
          $causevoxteams = $em->getRepository('AppBundle:Causevoxteam')->findAll();
          $causevoxfundraisers = $em->getRepository('AppBundle:Causevoxfundraiser')->findAll();

          $reportDate = $queryHelper->convertToDay(new DateTime());
          $reportDate->modify('-1 day');

          // replace this example code with whatever you need
          return $this->render('campaign/dashboard.html.twig', array(
            'campaign_settings' => $campaignSettings->getCampaignSettings(),
            'new_teacher_awards' => $queryHelper->getTeacherAwards(array('before_date' => $reportDate, 'limit' => 5, 'order_by' => array('field' => 'donated_at',  'order' => 'asc'))),
            'teacher_rankings' => $queryHelper->getTeacherRanks(array('limit'=> $limit, 'before_date' => $reportDate)),
            'report_date' => $reportDate,
            'ranking_limit' => $limit,
            'causevoxteams' => $causevoxteams,
            'causevoxfundraisers' => $causevoxfundraisers,
            'student_rankings' => $queryHelper->getStudentRanks(array('limit'=> $limit, 'before_date' => $reportDate)),
            'totals' => $queryHelper->getTotalDonations(array('before_date' => $reportDate)),
            'campaign' => $em->getRepository('AppBundle:Campaign')->findOneByUrl($url),
          ));


        }

}
