<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use App\Utils\CampaignHelper;
use App\Entity\Teacher;
use App\Entity\Campaign;
use App\Entity\CampaignUser;
use App\Utils\QueryHelper;
use Doctrine\ORM\Query\Expr;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
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

class DefaultController extends Controller
{

  /**
   * @Route("/", name="homepage")
   */
  public function indexAction(Request $request, LoggerInterface $logger)
  {

      $entity = 'Campaign';

      $em = $this->getDoctrine()->getManager();

      if(null !== $request->query->get('action') && $request->query->get('action') === 'new_campaign'){
          $action = $request->query->get('action');
          $campaign = new Campaign();
          $form = $this->createForm('App\Form\CampaignType');
          $form->handleRequest($request);

          $logger->debug("Logged in User ID: ".$this->getUser()->getId());
          $user = $this->getDoctrine()->getRepository('App:User')->find($this->getUser()->getId());
          $logger->debug("User ID: ".$user->getId());

          //TODO: Add custom validation
          if ($form->isSubmitted()) {
              $logger->debug("Creating new Campaign");
              $em = $this->getDoctrine()->getManager();
              $em->persist($campaign);
              $em->flush();

              return $this->redirectToRoute('homepage', array('id' => $campaign->getId()));
          }

          return $this->render('campaign/campaign.new.html.twig', array(
              'form' => $form->createView(),
              'entity' => $entity,
          ));
      }


      $qb = $em->createQueryBuilder()->select('c')
           ->from('App:Campaign', 'c')
           ->join('App:CampaignUser', 'cu')
           ->where('cu.campaign = c.id')
           ->andWhere('cu.user = :user')
           ->setParameter('user', $this->get('security.token_storage')->getToken()->getUser());

      $campaigns = $qb->getQuery()->getResult();

      return $this->render('campaign/campaign.index.html.twig', array(
          'campaigns' => $campaigns,
          'entity' => $entity,
      ));
  }




  /**
   * Displays a form to edit an existing Campaign entity.
   *
   * @Route("/create_campaign", name="campaign_new")
   * @Method({"GET", "POST"})
   */
  public function newAction(Request $request, LoggerInterface $logger, ValidatorInterface $validator)
  {
      
      $em = $this->getDoctrine()->getManager();
      $entity = 'Campaign';

      $campaign = new Campaign();

      $user = $this->get('security.token_storage')->getToken()->getUser();

      $editForm = $this->createFormBuilder($campaign)
      ->add('name', TextType::class, array('required' => true))
      ->add('description', TextareaType::class, array( 'attr' => array('class' => 'tinymce')))
      ->add('url')
      ->add('teamsFlag', CheckboxType::class, array('label'    => 'Enable Teams', 'required' => false))
      ->add('start_date', DateType::class, array('widget' => 'single_text' ))
      ->add('endDate', DateType::class, array('widget' => 'single_text'))
      ->add('email', HiddenType::class, array('data' => $user->getEmail()))      
      ->add('fundingGoal', MoneyType::class, array('required' => true, 'currency' => 'USD'))
      ->add('paypalEmail', EmailType::class, array('required' => false))->getForm();
  ;


    $editForm->handleRequest($request);

    if ($editForm->isSubmitted()) {
        $logger->info('Create Campaign Form Submitted');
        $campaign = $editForm->getData();

        $errors = $validator->validate($campaign);

        $fail = false;
        if (count($errors) > 0) {
            /*
                * Uses a __toString method on the $errors variable which is a
                * ConstraintViolationList object. This gives us a nice string
                * for debugging.
                */
            $errorsString = (string) $errors;
            $fail=true;

            $this->addFlash(
                'danger',
                'Campaign not created!<br>errors:'.$errorsString
            );
        }

        
        if(!$fail){

            $campaign->setEmail($user->getEmail());
            $campaign->setCreatedBy($user);
            $campaign->setTheme('Default');
            $campaign->setOnlineFlag(false);
            $campaign->setDonationFlag(false);

            $em->persist($campaign);

            /*
            * CREATE CAMPAIGN USER
            */
            $campaignUser = new CampaignUser();
            $campaignUser->setUser($user);
            $campaignUser->setCampaign($campaign);
            $em->persist($campaignUser);

            $em->flush();

            $this->addFlash(
            'success',
            'Campaign created!'
            );

            return $this->redirectToRoute('campaign_index', array('campaignUrl'=> $campaign->getUrl()));
        }

    }
      return $this->render('campaign/campaign.edit.html.twig', array(
          'edit_form' => $editForm->createView(),
          'entity' => $entity,
      ));
  }




}
