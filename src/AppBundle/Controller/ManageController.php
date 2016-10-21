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
          'new_teacher_awards' => $queryHelper->getTeacherAwards(array('limit' => 10, 'order_by' => array('field' => 'donated_at',  'order' => 'asc'))),
          'teacher_rankings' => $queryHelper->getTeacherRanks(array('limit'=> 10)),
          'student_rankings' => $queryHelper->getStudentRanks(array('limit'=> 10)),
          'totals' => $queryHelper->getTotalDonations(array()),
        ));
    }


    /**
     * @Route("/send_daily_email", name="manage_teacher_daily_email")
     */
    public function sendDailyEmailAction(Request $request)
    {
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();
        $teachers = $this->getDoctrine()->getRepository('AppBundle:Teacher')->findAll();

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
        $newAwards = $queryHelper->getNewTeacherAwards(array('before_date' => $reportDate, 'id' => $teacher->getId(), 'order_by' => array('field' => 'donation_amount',  'order' => 'asc')));
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



}
