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

/**
 * Manage Campaign controller.
 *
 * @Route("/{campaignUrl}")
 */
class CampaignController extends Controller
{

  /**
   * @Route("/", name="campaign_index")
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
   * Finds and displays a Campaign entity.
   *
   * @Route("/show/{id}", name="campaign_show")
   * @Method("GET")
   */
  public function showAction(Campaign $campaign)
  {
      $logger = $this->get('logger');
      $entity = 'Campaign';
      $deleteForm = $this->createDeleteForm($campaign);

      return $this->render('campaign/show.html.twig', array(
          'campaign' => $campaign,
          'delete_form' => $deleteForm->createView(),
          'entity' => $entity,
      ));
  }

  /**
   * Displays a form to edit an existing Campaign entity.
   *
   * @Route("/settings", name="campaign_edit")
   * @Method({"GET", "POST"})
   */
  public function editAction(Request $request, $campaignUrl)
  {
      $em = $this->getDoctrine()->getManager();
      $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
      $logger = $this->get('logger');
      $entity = 'Campaign';
      $deleteForm = $this->createDeleteForm($campaign);
      $editForm = $this->createForm('AppBundle\Form\CampaignType', $campaign);
      $editForm->handleRequest($request);

      if ($editForm->isSubmitted()) {
          $em = $this->getDoctrine()->getManager();
          $em->persist($campaign);
          $em->flush();

          $this->addFlash(
            'success',
            'Campaigns Saved!'
          );

          return $this->redirectToRoute('campaign_index', array('campaignUrl'=> $campaignUrl));
      }

      return $this->render('campaign/campaign.edit.html.twig', array(
          'campaign' => $campaign,
          'edit_form' => $editForm->createView(),
          'delete_form' => $deleteForm->createView(),
          'entity' => $entity,
      ));
  }

  /**
   * Deletes a Campaign entity.
   *
   * @Route("/delete", name="campaign_delete")
   * @Method("DELETE")
   */
  public function deleteAction(Request $request, $campaign)
  {
      $logger = $this->get('logger');
      $entity = 'Campaign';
      $form = $this->createDeleteForm($campaign);
      $form->handleRequest($request);

      if ($form->isSubmitted() && $form->isValid()) {
          $em = $this->getDoctrine()->getManager();
          $em->remove($campaign);
          $em->flush();
      }

      return $this->redirectToRoute('homepage');
  }

  /**
   * Creates a form to delete a Campaign entity.
   *
   * @param Campaign $campaign The Campaign entity
   *
   * @return \Symfony\Component\Form\Form The form
   */
  private function createDeleteForm($campaign)
  {
      $entity = 'Campaign';
      return $this->createFormBuilder()
          ->setAction($this->generateUrl('campaign_delete', array('campaignUrl' => $campaign->getUrl())))
          ->setMethod('DELETE')
          ->getForm()
      ;
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



}
