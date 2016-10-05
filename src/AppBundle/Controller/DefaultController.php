<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Utils\CampaignHelper;
use AppBundle\Entity\Teacher;
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
        $limit = 3;
        $em = $this->getDoctrine()->getManager();
        $queryHelper = new QueryHelper($em, $logger);
        $campaignSettings = new CampaignHelper($this->getDoctrine()->getRepository('AppBundle:Campaignsetting')->findAll());
        $causevoxteams = $em->getRepository('AppBundle:Causevoxteam')->findAll();
        $causevoxfundraisers = $em->getRepository('AppBundle:Causevoxfundraiser')->findAll();

        $reportDate = $queryHelper->convertToDay(new DateTime());
        $reportDate->modify('-1 day');

        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', array(
          'campaign_settings' => $campaignSettings->getCampaignSettings(),
          'new_teacher_awards' => $queryHelper->getNewTeacherAwards(array('before_date' => $reportDate)),
          'teacher_rankings' => $queryHelper->getTeacherRanks(array('limit'=> $limit, 'before_date' => $reportDate)),
          'report_date' => $reportDate,
          'ranking_limit' => $limit,
          'causevoxteams' => $causevoxteams,
          'causevoxfundraisers' => $causevoxfundraisers,
          'student_rankings' => $queryHelper->getStudentRanks(array('limit'=> $limit, 'before_date' => $reportDate)),
          'totals' => $queryHelper->getTotalDonations(array('before_date' => $reportDate)),
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
     * Lists all Teacher entities.
     *
     * @Route("/teacher", name="public_teacher_index")
     * @Method({"GET", "POST"})
     */
    public function TeacherIndexAction()
    {
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();

        $queryHelper = new QueryHelper($em, $logger);
        $reportDate = $queryHelper->convertToDay(new DateTime());
        $reportDate->modify('-1 day');


        // replace this example code with whatever you need
        return $this->render('default/teacherIndex.html.twig', array(
          'teachers' => $queryHelper->getTeacherRanks(array('limit'=> 0, 'before_date' => $reportDate))
        ));

    }

    /**
     * Finds and displays a Teacher entity.
     *
     * @Route("/teacher/{id}", name="public_teacher_show")
     * @Method("GET")
     */
    public function showAction(Teacher $teacher)
    {
        $logger = $this->get('logger');
        $teacher = $this->getDoctrine()->getRepository('AppBundle:Teacher')->findOneById($teacher->getId());
        //$logger->debug(print_r($student->getDonations()));
        $em = $this->getDoctrine()->getManager();

        $qb = $em->createQueryBuilder()->select('u')
               ->from('AppBundle:Campaignaward', 'u')
               ->orderBy('u.amount', 'DESC');

        $campaignAwards = $qb->getQuery()->getResult();
        $campaignSettings = new CampaignHelper($this->getDoctrine()->getRepository('AppBundle:Campaignsetting')->findAll());

        $queryHelper = new QueryHelper($em, $logger);
        $reportDate = $queryHelper->convertToDay(new DateTime());
        $reportDate->modify('-1 day');

        return $this->render('default/teacherShow.html.twig', array(
            'teacher' => $teacher,
            'teacher_rank' => $queryHelper->getTeacherRank($teacher->getId(),array('limit' => 0, 'before_date' => $reportDate)),
            'campaign_awards' => $campaignAwards,
            'campaignsettings' => $campaignSettings->getCampaignSettings(),
        ));
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
