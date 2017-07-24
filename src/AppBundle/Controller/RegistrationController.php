<?php

namespace AppBundle\Controller;

use AppBundle\Form\UserType;
use AppBundle\Entity\User;
use AppBundle\Entity\Campaign;
use AppBundle\Entity\CampaignUser;
use AppBundle\Entity\UserStatus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use DateTime;
use DateTimeZone;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use AppBundle\Utils\CampaignHelper;

/**
 * Security controller.
 *
 */
class RegistrationController extends Controller
{
    /**
     * @Route("/signup", name="user_registration")
     */
    public function registerAction(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();

        // 1) build the form
        $user = new User();
        $form = $this->createForm(UserType::class, $user);


        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);
        if ($form->isSubmitted()) {

            // 3) Encode the password (you could also do this via Doctrine listener)
            $password = $passwordEncoder->encodePassword($user, $user->getPassword());
            $user->setPassword($password);
            $user->setApiKey($password);
            $user->setUsername($user->getEmail());
            $user->setEmailConfirmationCode(base64_encode(random_bytes(20)));
            $user->setEmailConfirmationCodeTimestamp(new \DateTime());
            //Get User Status
            $userStatus = $em->getRepository('AppBundle:UserStatus')->findOneByName('Registered');

            if(!empty($userStatus)){
              $logger->debug('UserStatus of Registered could not be found');
            }

            $user->setUserStatus($userStatus);


            // 4) save the User!
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $campaignHelper = new CampaignHelper($em, $logger);
            $campaign = $campaignHelper->loadCampaign($this->getCampaignData($user), array());


            // ... do any other work - like sending them an email, etc
            // maybe set a "flash" success message for the user

            return $this->redirectToRoute('campaign_index', array('campaignUrl' => $campaign->getUrl()));
        }

        return $this->render('registration/register.html.twig',
            array(
              'form' => $form->createView(),
            )
        );
    }



    function getCampaignData(User $user){

        $date = new DateTime();
        $date->modify('-1 month');
        $campaignStartDate = $date;

        $date = new DateTime();
        $date->modify('+2 months');
        $campaignEndDate = $date;

        $data = array(
          'user' => $user,
          'campaign' => array(
            'name' => 'My First Campaign',
            'description' => 'This is where the description will go for your campaign',
            'theme' => 'cerulean',
            'email' => $user->getEmail(),
            'fundingGoal' => 10000,
            'createdBy' => $user,
            'startDate' => $campaignStartDate,
            'endDate' => $campaignEndDate,
            'grades'=> array(
              0 => array(
                'name'=>'Kindergarten',
                'teachers' => array(
                  0 => array(
                    'teacherName' => 'Ms. Llane',
                    'email' => '',
                    'students' => array(
                      0 => array(
                        'name' => 'Gregory H.',
                        'donations' => array(
                          0 => array(
                            'amount' => 25.00,
                            'type' => 'manual',
                            'source' => 'manual',
                          )
                        )
                      )
                    )
                  )
                )
              )
            )
          ));

          return $data;
    }

}
