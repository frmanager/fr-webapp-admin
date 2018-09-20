<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use App\Utils\CampaignHelper;
use App\Entity\Classroom;
use App\Utils\QueryHelper;
use App\Entity\Campaign;
use App\Utils\DonationHelper;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
  public function dashboardAction(Request $request, $campaignUrl, LoggerInterface $logger)
  {
      
      $em = $this->getDoctrine()->getManager();

      //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
      $campaign = $em->getRepository('App:Campaign')->findOneByUrl($campaignUrl);
      if(is_null($campaign)){
        $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this campaign.');
        return $this->redirectToRoute('homepage');
      }

        //CODE TO CHECK TO SEE IF USER HAS PERMISSIONS TO CAMPAIGN
      $campaignHelper = new CampaignHelper($em, $logger);
      if(!$campaignHelper->campaignPermissionsCheck($this->get('security.token_storage')->getToken()->getUser(), $campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'You do not have permissions to this campaign.');
          return $this->redirectToRoute('homepage');
      }


      if(null !== $request->query->get('action')){
          $action = $request->query->get('action');

          if($action === 'reload_donation_database'){

            $logger->debug("Doing a Donation Database Refresh");
            $donationHelper = new DonationHelper($em, $logger);
            $donationHelper->reloadDonationDatabase(array('campaign'=>$campaign));
            $this->get('session')->getFlashBag()->add('success', 'Reloaded Donation Database');

          }else if($action === 'download_donation_ledger'){

            $queryString = sprintf('SELECT s.id as student_id,
                          c.id as classroom_id,
                          concat(g.name, \' - \', c.name) as classroom,
                          s.name as student_name,
                          \'\' as donation_amount
                     FROM App:Student s
            LEFT OUTER JOIN App:Classroom c
                     WITH c.id = s.classroom
            LEFT OUTER JOIN App:Grade g
                     WITH g.id = c.grade
                    WHERE s.campaign = %s
                 GROUP BY s.id
                 ORDER BY concat(c.name, \'-\', g.name) ASC,  student_name ASC', $campaign->getId());


            $results =  $em->createQuery($queryString)->getResult();

            $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
            $filename = $this->getParameter('protected_download_directory').'/'.$campaign->getId().'_donation_ledger.csv';

            file_put_contents(
                $filename,
                $serializer->encode($results, 'csv')
            );

            $response = new BinaryFileResponse($filename);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

            return $response;

          }

      }





      $queryHelper = new QueryHelper($em, $logger);

      // replace this example code with whatever you need
      return $this->render('campaign/dashboard.html.twig', array(
        'new_classroom_awards' => $queryHelper->getClassroomAwards(array('campaign' => $campaign,'limit' => 10, 'order_by' => array('field' => 'donated_at',  'order' => 'asc'))),
        'classroom_rankings' => $queryHelper->getClassroomRanks(array('campaign' => $campaign,'limit'=> 10)),
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
  public function showAction(Campaign $campaign, LoggerInterface $logger)
  {
      
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
  public function editAction(Request $request, $campaignUrl, LoggerInterface $logger)
  {
      
      $em = $this->getDoctrine()->getManager();
      $entity = 'Campaign';
      $campaign = $em->getRepository('App:Campaign')->findOneByUrl($campaignUrl);

      //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
      $campaign = $em->getRepository('App:Campaign')->findOneByUrl($campaignUrl);
      if(is_null($campaign)){
        $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this campaign.');
        return $this->redirectToRoute('homepage');
      }

      //CODE TO CHECK TO SEE IF USER HAS PERMISSIONS TO CAMPAIGN
      $campaignHelper = new CampaignHelper($em, $logger);
      if(!$campaignHelper->campaignPermissionsCheck($this->get('security.token_storage')->getToken()->getUser(), $campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'You do not have permissions to this campaign.');
          return $this->redirectToRoute('homepage');
      }


      $deleteForm = $this->createDeleteForm($campaign);
      
      $editForm = $this->createFormBuilder($campaign)
      ->add('name', TextType::class, array('required' => true))
      ->add('description', TextareaType::class, array( 'attr' => array('class' => 'tinymce')))
      ->add('url')
      ->add('onlineFlag', CheckboxType::class, array('label'    => 'Put campaign online', 'required' => false))
      ->add('teamsFlag', CheckboxType::class, array('label'    => 'Enable Teams', 'required' => false))
      ->add('email', TextType::class, array('required' => true))
      ->add('theme', ChoiceType::class, array(
            'choices'  => array(
                'Default' => 'cerulean',
                'Cerulean' => 'cerulean',
                'Cosmo' => 'cosmo',
                'Cyborg' => 'cyborg',
                'Darkly' => 'darkly',
                'Flatly' => 'flatly',
                'Journal' => 'journal',
                'Litera' => 'litera',
                'Lumen' => 'lumen',
                'Lux' => 'lux',
                'Materia' => 'materia',
                'Minty' => 'minty',
                'Pulse' => 'pulse',
                'Sandstone' => 'sandstone',
                'Simplex' => 'simplex',
                'Slate' => 'slate',
                'Solar' => 'solar',
                'Space Lab' => 'spacelab',
                'Superhero' => 'superhero',
                'United' => 'united',
                'Yeti' => 'yeti',
            )))
      ->add('start_date', DateType::class, array('widget' => 'single_text' ))
      ->add('endDate', DateType::class, array('widget' => 'single_text'))
      ->add('fundingGoal', MoneyType::class, array('required' => true, 'currency' => 'USD'))
      ->add('donationFlag', CheckboxType::class, array('label'    => 'Enable Donations', 'required' => false))
      ->add('tippingFlag', CheckboxType::class, array('label'    => 'Enable Tipping', 'required' => false, 'disabled' => true))
      ->add('paypalEmail', EmailType::class, array('required' => false))->getForm();
  ;
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
  public function deleteAction(Request $request, $campaign, LoggerInterface $logger)
  {
      
      $entity = 'Campaign';


      //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
      if(is_null($campaign)){
        $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this campaign.');
        return $this->redirectToRoute('homepage');
      }

      //CODE TO CHECK TO SEE IF USER HAS PERMISSIONS TO CAMPAIGN
      $campaignHelper = new CampaignHelper($em, $logger);
      if(!$campaignHelper->campaignPermissionsCheck($this->get('security.token_storage')->getToken()->getUser(), $campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'You do not have permissions to this campaign.');
          return $this->redirectToRoute('homepage');
      }


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
   * @Route("/send_daily_email", name="manage_classroom_daily_email")
   */
  public function sendDailyEmailAction(Request $request, $campaignUrl, LoggerInterface $logger)
  {
      
      $em = $this->getDoctrine()->getManager();
      $classrooms = $this->getDoctrine()->getRepository('App:Classroom')->findAll();
      $campaign = $em->getRepository('App:Campaign')->findOneByUrl($campaignUrl);
      $queryHelper = new QueryHelper($em, $logger);

      $reportDate = $queryHelper->convertToDay(new DateTime());

      if(null !== $request->query->get('date_modify')){
        $reportDate->modify($request->query->get('date_modify').' day');
      }

      $logger->info("Sending Daily Email");
      $emailCount = 0;
      foreach ($classrooms as $classroom) {
      unset($newAwards);
      $newAwards = $queryHelper->getNewClassroomAwards(array('campaign' => $campaign, 'before_date' => $reportDate, 'id' => $classroom->getId(), 'order_by' => array('field' => 'donation_amount',  'order' => 'asc')));
      $logger->debug("New Awards for: ".print_r($newAwards, true));
      if(isset($newAwards) && !empty($newAwards) && count($newAwards) > 0){
        if (strcmp($this->container->get('kernel')->getEnvironment(), "dev") == 0){
          $toAddress = 'funrun@lrespto.org';
        }else{
          $toAddress = $classroom->getEmail();
        }
        $emailCount ++;
        $logger->info("Sending Daily Email to: ".$classroom->getClassroomName());
        $message = \Swift_Message::newInstance()
                ->setSubject('New Fun Run Award Level Reached!')
                ->setFrom('support@lrespto.org')
                ->setCc('funrun@lrespto.org', 'support@lrespto.org')
                ->setTo($toAddress)
                ->setBody(
                    $this->renderView(
                        // app/Resources/views/Emails/registration.html.twig
                        'email/classroomAwards.html.twig',
                        array(
                          'classroom' => $classroom,
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
        $logger->info("Classroom ".$classroom->getClassroomName()." did not have any new awards.");
      }
        }

        $this->addFlash(
            'success',
            'Sent '.$emailCount.' emails'
        );

        return $this->redirectToRoute('manage_index');
  }

}
