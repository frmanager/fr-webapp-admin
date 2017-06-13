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
    $securityContext = $this->container->get('security.authorization_checker');
    if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
        $this->addFlash(
            'success',
            'Already logged in'
        );

        $logger = $this->get('logger');
        $entity = 'Campaign';
        $em = $this->getDoctrine()->getManager();

        return $this->render('campaign/campaign.index.html.twig', array(
            'campaigns' => $em->getRepository('AppBundle:Campaign')->findAll(),
            'entity' => $entity,
        ));
    } else {
        return $this->redirectToRoute('fos_user_security_login');
    }
  }


  /**
   * @Route("/send_daily_email", name="manage_teacher_daily_email")
   */
  public function sendDailyEmailAction(Request $request, $campaignUrl)
  {
      $logger = $this->get('logger');
      $em = $this->getDoctrine()->getManager();
      $teachers = $this->getDoctrine()->getRepository('AppBundle:Teacher')->findAll();
      $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
      $queryHelper = new QueryHelper($em, $logger);

      $campaignSettings = new CampaignHelper($this->getDoctrine()->getRepository('AppBundle:Campaignsetting')->findAll());

      $reportDate = $queryHelper->convertToDay(new DateTime());

      if(null !== $request->query->get('date_modify')){
        $reportDate->modify($request->query->get('date_modify').' day');
      }

      $logger->info("Sending Daily Email");
      $emailCount = 0;
      foreach ($teachers as $teacher) {
      unset($newAwards);
      $newAwards = $queryHelper->getNewTeacherAwards(array('campaign' => $campaign, 'before_date' => $reportDate, 'id' => $teacher->getId(), 'order_by' => array('field' => 'donation_amount',  'order' => 'asc')));
      $logger->debug("New Awards for: ".print_r($newAwards, true));
      if(isset($newAwards) && !empty($newAwards) && count($newAwards) > 0){
        if (strcmp($this->container->get('kernel')->getEnvironment(), "dev") == 0){
          $toAddress = 'funrun@lrespto.org';
        }else{
          $toAddress = $teacher->getEmail();
        }
        $emailCount ++;
        $logger->info("Sending Daily Email to: ".$teacher->getTeacherName());
        $message = \Swift_Message::newInstance()
                ->setSubject('New Fun Run Award Level Reached!')
                ->setFrom('support@lrespto.org')
                ->setCc('funrun@lrespto.org', 'support@lrespto.org')
                ->setTo($toAddress)
                ->setBody(
                    $this->renderView(
                        // app/Resources/views/Emails/registration.html.twig
                        'email/teacherAwards.html.twig',
                        array(
                          'teacher' => $teacher,
                          'report_date' => $reportDate,
                          'awards' => $newAwards
                        )
                    ),
                    'text/html'
                )
                /*
                 * If you also want to include a plaintext version of the message
                ->addPart(
                    $this->renderView(
                        'Emails/registration.txt.twig',
                        array('name' => $name)
                    ),
                    'text/plain'
                )
                */
            ;
            $this->get('mailer')->send($message);
      }else{
        $logger->info("Teacher ".$teacher->getTeacherName()." did not have any new awards.");
      }
        }

        $this->addFlash(
            'success',
            'Sent '.$emailCount.' emails'
        );

        return $this->redirectToRoute('manage_index');
  }



  /**
   * @Route("/{campaignUrl}", name="campaign_dashboard")
   */
  public function dashboardAction($campaignUrl)
  {
      $logger = $this->get('logger');
      $em = $this->getDoctrine()->getManager();
      $queryHelper = new QueryHelper($em, $logger);
      $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
      $campaignSettings = new CampaignHelper($this->getDoctrine()->getRepository('AppBundle:Campaignsetting')->findAll());

      // replace this example code with whatever you need
      return $this->render('campaign/dashboard.html.twig', array(
        'campaign_settings' => $campaignSettings->getCampaignSettings(),
        'new_teacher_awards' => $queryHelper->getTeacherAwards(array('campaign' => $campaign,'limit' => 10, 'order_by' => array('field' => 'donated_at',  'order' => 'asc'))),
        'teacher_rankings' => $queryHelper->getTeacherRanks(array('campaign' => $campaign,'limit'=> 10)),
        'student_rankings' => $queryHelper->getStudentRanks(array('campaign' => $campaign,'limit'=> 10)),
        'totals' => $queryHelper->getTotalDonations(array('campaign' => $campaign)),
        'campaign' => $campaign,
      ));
  }


  /**
   * Set Campaign entities.
   *
   * @Route("/set/{id}", name="campaign_set")
   * @Method("GET")
   */
   public function setAction(Request $request, Campaign $campaign)
   {
       $logger = $this->get('logger');
       $entity = 'Campaign';
       if($this->container->get('session')->isStarted()){
         $logger->debug("THERE IS A SESSION");
       }
       $this->get('session')->set('campaign', $campaign);

       return $this->redirectToRoute('homepage');
   }


 /**
  * Creates a new Campaign entity.
  *
  * @Route("/new", name="campaign_new")
  * @Method({"GET", "POST"})
  */
 public function newAction(Request $request)
 {
     $logger = $this->get('logger');
     $entity = 'Campaign';
     $campaign = new Campaign();
     $form = $this->createForm('AppBundle\Form\CampaignType', $campaign);
     $form->handleRequest($request);

     $logger->debug("Logged in User ID: ".$this->getUser()->getId());
     $user = $this->getDoctrine()->getRepository('AppBundle:User')->find($this->getUser()->getId());
     $logger->debug("User ID: ".$user->getId());

     //TODO: Add custom validation
     if ($form->isSubmitted()) {
         $logger->debug("Creating new Campaign");
         $em = $this->getDoctrine()->getManager();
         $em->persist($campaign);
         $em->flush();

         return $this->redirectToRoute('homepage', array('id' => $campaign->getId()));
     }

     return $this->render('crud/new.html.twig', array(
         'campaign' => $campaign,
         'form' => $form->createView(),
         'entity' => $entity,
     ));
 }



}
