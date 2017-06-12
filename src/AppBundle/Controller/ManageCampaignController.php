<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Campaign;
use AppBundle\Utils\CampaignHelper;
use AppBundle\Utils\QueryHelper;
use DateTime;

/**
 * Campaign Manager controller.
 *
 * @Route("/manage")
 */
class ManageCampaignController extends Controller
{


    /**
     * Lists all Campaign entities.
     *
     * @Route("/", name="campaignManager_index")
     * @Method("GET")
     */
     public function indexAction()
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
      * @Route("/{campaignUrl}", name="campaignManager_dashboard")
      */
     public function dashboardAction($campaignUrl)
     {
         $logger = $this->get('logger');
         $em = $this->getDoctrine()->getManager();
         $queryHelper = new QueryHelper($em, $logger);
         $campaignSettings = new CampaignHelper($this->getDoctrine()->getRepository('AppBundle:Campaignsetting')->findAll());

         // replace this example code with whatever you need
         return $this->render('campaignManager/dashboard.html.twig', array(
           'campaign_settings' => $campaignSettings->getCampaignSettings(),
           'new_teacher_awards' => $queryHelper->getTeacherAwards(array('limit' => 10, 'order_by' => array('field' => 'donated_at',  'order' => 'asc'))),
           'teacher_rankings' => $queryHelper->getTeacherRanks(array('limit'=> 10)),
           'student_rankings' => $queryHelper->getStudentRanks(array('limit'=> 10)),
           'totals' => $queryHelper->getTotalDonations(array()),
           'campaign' => $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl),
         ));
     }


     /**
      * Set Campaign entities.
      *
      * @Route("/set/{id}", name="campaignManager_set")
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
     * @Route("/new", name="campaignManager_new")
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

            return $this->redirectToRoute('campaignManager_index', array('id' => $campaign->getId()));
        }

        return $this->render('crud/new.html.twig', array(
            'campaign' => $campaign,
            'form' => $form->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Finds and displays a Campaign entity.
     *
     * @Route("/show/{id}", name="campaignManager_show")
     * @Method("GET")
     */
    public function showAction(Campaign $campaign)
    {
        $logger = $this->get('logger');
        $entity = 'Campaign';
        $deleteForm = $this->createDeleteForm($campaign);

        return $this->render('CampaignManager/show.html.twig', array(
            'campaign' => $campaign,
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing Campaign entity.
     *
     * @Route("/edit/{id}", name="campaignManager_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Campaign $campaign)
    {
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

            return $this->redirectToRoute('campaignManager_index', array('id' => $campaign->getId()));
        }

        return $this->render('crud/edit.html.twig', array(
            'campaign' => $campaign,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Deletes a Campaign entity.
     *
     * @Route("/delete/{id}", name="campaignManager_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Campaign $campaign)
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

        return $this->redirectToRoute('campaignManager_index');
    }

    /**
     * Creates a form to delete a Campaign entity.
     *
     * @param Campaign $campaign The Campaign entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Campaign $campaign)
    {
        $entity = 'Campaign';
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('campaignManager_delete', array('id' => $campaign->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * @Route("/send_daily_email", name="campaignManager_teacher_daily_email")
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

          return $this->redirectToRoute('campaignManager_index');
    }

}
