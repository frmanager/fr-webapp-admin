<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Utils\CampaignHelper;
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
        $em = $this->getDoctrine()->getManager();
        $queryHelper = new QueryHelper($em, $logger);
        $campaignSettings = new CampaignHelper($this->getDoctrine()->getRepository('AppBundle:Campaignsetting')->findAll());
        $causevoxteams = $em->getRepository('AppBundle:Causevoxteam')->findAll();
        $causevoxfundraisers = $em->getRepository('AppBundle:Causevoxfundraiser')->findAll();
        $tempDate = new DateTime();
        $dateString = $tempDate->format('Y-m-d').' 00:00:00';
        $reportDate = DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
        $reportDate->modify('-1 day');
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', array(
          'campaign_settings' => $campaignSettings->getCampaignSettings(),
          'new_teacher_awards' => $queryHelper->getNewTeacherAwards(array('day_modifier' => '-1 day')),
          'teacher_rankings' => $queryHelper->getTeacherRanks(array('limit'=> 10, 'day_modifier' => '-1 day')),
          'report_date' => $reportDate,
          'causevoxteams' => $causevoxteams,
          'causevoxfundraisers' => $causevoxfundraisers,
          'student_rankings' => $queryHelper->getStudentRanks(array('limit'=> 10, 'day_modifier' => '-1 day')),
          'totals' => $queryHelper->getTotalDonations(array('day_modifier' => '-1 day')),
        ));
    }

    /**
     * @Route("/login_check", name="login_check")
     */
    public function loginCheckAction(Request $request)
    {
        $securityContext = $this->container->get('security.authorization_checker');
        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $this->addFlash(
                'success',
                'Already logged in'
            );

            return $this->redirectToRoute('manage_index');
        } else {
            return $this->redirectToRoute('fos_user_security_login');
        }
    }


    /**
     * @Route("/faq", name="faq")
     */
    public function faqAction(Request $request)
    {
      $logger = $this->get('logger');
      $em = $this->getDoctrine()->getManager();
      $queryHelper = new QueryHelper($em, $logger);
      $campaignSettings = new CampaignHelper($this->getDoctrine()->getRepository('AppBundle:Campaignsetting')->findAll());
      $causevoxteams = $em->getRepository('AppBundle:Causevoxteam')->findAll();
      $causevoxfundraisers = $em->getRepository('AppBundle:Causevoxfundraiser')->findAll();

      return $this->render('default/faq.html.twig', array(
        'campaign_settings' => $campaignSettings->getCampaignSettings(),
        'causevoxteams' => $causevoxteams,
        'causevoxfundraisers' => $causevoxfundraisers,
      ));
    }


}
