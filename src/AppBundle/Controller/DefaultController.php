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

        // replace this example code with whatever you need
        return $this->render('default/homepage.html.twig', array(
          'campaigns' => $em->getRepository('AppBundle:Campaign')->findAll(),
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

            return $this->redirectToRoute('campaignManager_index');
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


        /**
         * Lists all Awards for teachers.
         *
         * @Route("/awards", name="public_teacher_awards")
         * @Method({"GET", "POST"})
         */
        public function TeacherAwardsAction()
        {
          $logger = $this->get('logger');
          $limit = 3;
          $em = $this->getDoctrine()->getManager();
          $queryHelper = new QueryHelper($em, $logger);
          $campaignSettings = new CampaignHelper($this->getDoctrine()->getRepository('AppBundle:Campaignsetting')->findAll());
          $reportDate = $queryHelper->convertToDay(new DateTime());
          $reportDate->modify('-1 day');

          // replace this example code with whatever you need
          return $this->render('default/teacherAwards.html.twig', array(
            'campaign_settings' => $campaignSettings->getCampaignSettings(),
            'teachers' => $queryHelper->getTeacherAwards(array('before_date' => $reportDate)),
            'report_date' => $reportDate,
          ));

        }


      /**
       * Lists all Campaign entities.
       *
       * @Route("/campaigns", name="campaign_index")
       * @Method("GET")
       */
       public function campaignIndexAction()
       {
           $logger = $this->get('logger');
           $entity = 'Campaign';
           $em = $this->getDoctrine()->getManager();

           return $this->render('CampaignManager/campaign.index.html.twig', array(
               'campaigns' => $em->getRepository('AppBundle:Campaign')->findAll(),
               'entity' => $entity,
           ));
       }



        /**
         * Show campaign dashboard.
         *
         * @Route("/{campaignUrl}", name="campaign_dashboard")
         * @Method("GET")
         */
         public function campaignDashboardAction($campaignUrl)
         {

           $logger = $this->get('logger');
           $limit = 3;
           $em = $this->getDoctrine()->getManager();

           $campaign =  $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);

           if(count($campaign) == 0){
             $this->addFlash(
                 'warning',
                 'Could not find Campaign'
             );
             return $this->redirectToRoute('campaign_index');
           }


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
             'campaign' => $campaign
           ));


         }




}
