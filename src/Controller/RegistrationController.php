<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use App\Form\UserType;
use App\Entity\User;
use App\Entity\Campaign;
use App\Entity\Campaignawardtype;
use App\Entity\Campaignawardstyle;
use App\Entity\Campaignaward;
use App\Entity\CampaignUser;
use App\Entity\Classroom;
use App\Entity\Grade;
use App\Entity\Student;
use App\Entity\UserStatus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use DateTime;
use DateTimeZone;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Security controller.
 * 
 */
class RegistrationController extends Controller
{

  /**
   * @Route("/signup", name="user_registration")
   */
  public function registerAction(Request $request, UserPasswordEncoderInterface $passwordEncoder, LoggerInterface $logger)
  {
      
      $em = $this->getDoctrine()->getManager();

      //Verifying if user is logged in, if their account is confirmed, and if their team already exists
      $securityContext = $this->container->get('security.authorization_checker');
      if ($securityContext->isGranted('ROLE_USER')) {
        $logger->debug("User is already logged in and has an account. Checking for email confirmation");
        $user = $this->get('security.token_storage')->getToken()->getUser();
        if($user->getUserStatus()->getName() !== "Confirmed"){
          return $this->redirectToRoute('confirm_email');
        }else{
          return $this->redirectToRoute('homepage');
        }
      }

      if ($request->isMethod('POST')) {
          $params = $request->request->all();
          $fail = false;

          if(!$fail && empty($params['user']['firstName'])){
            $this->addFlash('warning','First name is required');
            $fail = true;
          }

          if(!$fail && empty($params['user']['lastName'])){
            $this->addFlash('warning','Last name is required');
            $fail = true;
          }

          if(!$fail && empty($params['user']['email'])){
            $this->addFlash('warning','Email is required');
            $fail = true;
          }

          if(!$fail && empty($params['user']['Password']['first'])){
            $this->addFlash('warning','Password is required');
            $fail = true;
          }

          if(!$fail && empty($params['user']['Password']['second'])){
            $this->addFlash('warning','Confirmation Password is required');
            $fail = true;
          }

          if(!$fail && $params['user']['Password']['first'] !== $params['user']['Password']['second']){
            $this->addFlash('warning','Passwords do not match');
            $fail = true;
          }

          if(!$fail){
            $userCheck = $em->getRepository('App:User')->findOneByEmail($params['user']['email']);
            if(!is_null($userCheck)){
              $this->get('session')->getFlashBag()->add('warning', 'We apologize, an account already exists with this email.');
              return $this->render('registration/register.html.twig');
            }

            $user = new User();
            $password = $passwordEncoder->encodePassword($user, $params['user']['Password']['first']);
            $user->setPassword($password);
            $user->setApiKey($password);
            $user->setEmail($params['user']['email']);
            $user->setFirstName($params['user']['firstName']);
            $user->setLastName($params['user']['lastName']);
            $user->setUsername($params['user']['email']);
            $user->setFundraiserFlag(true);
            $user->setEmailConfirmationCode(strtoupper($this->generateRandomString(8)));
            $user->setEmailConfirmationCodeTimestamp(new \DateTime());
            //Get User Status
            $userStatus = $em->getRepository('App:UserStatus')->findOneByName('Registered');

            if(!empty($userStatus)){
              $logger->debug('UserStatus of Registered could not be found');
              $fail = true;
            }

            $user->setUserStatus($userStatus);

            // 4) save the User!
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);


            //Send Confirmation Email
            //Send Email
            $message = (new \Swift_Message("FR Manager account activation code"))
              ->setFrom('funrun@lrespto.org') //TODO: Change this to parameter for support email
              ->setTo($user->getEmail())
              ->setContentType("text/html")
              ->setBody(
                  $this->renderView('email/email_confirmation.email.twig', array('user' => $user))
              );

            $this->get('mailer')->send($message);

            //Create demo campaign for user
            $campaign = $this->createCampaign($user);

            // ... do any other work - like sending them an email, etc
            // maybe set a "flash" success message for the user
            $this->authenticateUser($user);
            $this->addFlash('success','Thanks for registering. You should receive an email with instructions on how to fully activate your account.');

            return $this->redirectToRoute('confirm_email');
          }
      }

      return $this->render('registration/register.html.twig');

  }



    /**
     * Confirms Email Address
     *
     * @Route("/confirm_email", name="confirm_email")
     *
     */
      public function emailConfirmationAction(Request $request, LoggerInterface $logger)
      {

          
          $logger->debug("Entering RegistrationController->emailConfirmationAction");
          $em = $this->getDoctrine()->getManager();

          $this->denyAccessUnlessGranted('ROLE_USER');

          $user = $this->get('security.token_storage')->getToken()->getUser();
          if(null !== $request->query->get('action')){
              $action = $request->query->get('action');

              if($action === 'resend_email_confirmation'){
                $user->setEmailConfirmationCode($this->generateRandomString(8));
                $user->setEmailConfirmationCodeTimestamp(new \DateTime());
                $em->persist($user);
                $em->flush();

                //Send Email
                $message = (new \Swift_Message("FR Manager account activation code"))
                  ->setFrom('funrun@lrespto.org') //TODO: Change this to parameter for support email
                  ->setTo($user->getEmail())
                  ->setContentType("text/html")
                  ->setBody(
                      $this->renderView('email/email_confirmation.email.twig', array('user' => $user))
                  );

                $this->get('mailer')->send($message);

                $this->get('session')->getFlashBag()->add('info', 'New code has been sent to your email, please check your inbox');
                return $this->redirectToRoute('confirm_email');
              }
          }

          if ($request->isMethod('POST')) {
              $fail = false;
              $params = $request->request->all();

              if(empty($params['user']['emailConfirmationCode'])){
                $this->addFlash('warning','Please input the Email Confirmation Code');
                $fail = true;
              }else{
                $confirmationCode = $params['user']['emailConfirmationCode'];
              }

              //see if the emailConfirmationCode is still Valid
              //Its only valid for 30 minutes
              //We take the timestamp in the database, add 30 minutes, and see if it is still greater than Now...
              $dateNow = (new \DateTime());
              $userEmailConfirmationCodeTimestamp = $user->getEmailConfirmationCodeTimestamp()->modify('+30 minutes');
              if(!$fail && $userEmailConfirmationCodeTimestamp < $dateNow){
                $logger->debug("Code is expired");
                $this->addFlash('warning','This confirmation code is expired');
                $fail = true;
              }

              if(!$fail && $user->getEmailConfirmationCode() !== $confirmationCode){
                $logger->debug("Code does not match what is in the database");
                $this->addFlash('warning','Confirmation code does not match our records');
                $fail = true;
              }

              if(!$fail){
                $this->addFlash('warning','Thank you for confirming your account');
                $user->setEmailConfirmationCode = null;
                $userStatus =  $em->getRepository('App:UserStatus')->findOneByName("CONFIRMED");
                $user->setUserStatus($userStatus);
                $user->setIsActive(true);
                $em->persist($user);
                $em->flush();

                return $this->redirectToRoute('homepage');

              }

          }

          return $this->render('registration/confirmed.html.twig', array('user' => $user));
      }






    private function authenticateUser(User $user)
    {
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);
        $this->get('session')->set('_security_main', serialize($token));
    }






    private function createCampaign(User $user){
      $em = $this->getDoctrine()->getManager();

      /*
      * CREATE BASE CAMPAIGN USER
      */
      $campaign = new Campaign();
      $campaign->setName("My First Campaign");
      $campaign->setDescription('This is where the description will go for your campaign');
      $campaign->setTheme('cerulean');

      //TODO: Verify this does not already exist
      $campaign->setUrl(strtolower($this->generateRandomString(12)));
      $campaign->setEmail($user->getEmail());
      $campaign->setFundingGoal(10000);
      $campaign->setCreatedBy($user);
      $campaign->setOnlineFlag(false);



      $date = new DateTime();
      $date->modify('-1 month');
      $campaign->setStartDate($date);

      $date = new DateTime();
      $date->modify('+2 month');
      $campaign->setEndDate($date);

      //Save
      $em->persist($campaign);

      /*
      * CREATE CAMPAIGN USER
      */
      $campaignUser = new CampaignUser();
      $campaignUser->setUser($user);
      $campaignUser->setCampaign($campaign);
      $em->persist($campaignUser);

      /*
      * CREATE CAMPAIGN AWARDS
      */

      //Step 1: Get base ID's
      $campaignAwardTypeClassroom = $em->getRepository('App:Campaignawardtype')->findOneByValue('classroom');
      $campaignAwardTypeStudent = $em->getRepository('App:Campaignawardtype')->findOneByValue('student');
      $campaignAwardStyleLevel = $em->getRepository('App:Campaignawardstyle')->findOneByValue('level');
      $campaignAwardStylePlace = $em->getRepository('App:Campaignawardstyle')->findOneByValue('place');

      //Classroom Place Awards
      $this->createCampaignAward($campaign, "First Place Classroom Award", "First Place Classroom Award Description", $campaignAwardTypeClassroom, $campaignAwardStylePlace, null, 1);
      $this->createCampaignAward($campaign, "Second Place Classroom Award", "Second Place Classroom Award Description", $campaignAwardTypeClassroom, $campaignAwardStylePlace, null, 2);
      $this->createCampaignAward($campaign, "Third Place Classroom Award", "Third Place Classroom Award Description", $campaignAwardTypeClassroom, $campaignAwardStylePlace, null, 3);

      //Student Place Awards
      $this->createCampaignAward($campaign, "First Place Student Award", "First Place Student Award Description", $campaignAwardTypeStudent, $campaignAwardStylePlace, null, 1);
      $this->createCampaignAward($campaign, "Second Place Student Award", "Second Place Student Award Description", $campaignAwardTypeStudent, $campaignAwardStylePlace, null, 2);
      $this->createCampaignAward($campaign, "Third Place Student Award", "Third Place Student Award Description", $campaignAwardTypeStudent, $campaignAwardStylePlace, null, 3);

      //Classroom Level Awards
      $this->createCampaignAward($campaign, "First Level Classroom Award", "First Level Classroom Award Description", $campaignAwardTypeClassroom, $campaignAwardStylePlace, 50, null);
      $this->createCampaignAward($campaign, "Second Level Classroom Award", "Second Level Classroom Award Description", $campaignAwardTypeClassroom, $campaignAwardStylePlace, 100, null);
      $this->createCampaignAward($campaign, "Third Level Classroom Award", "Third Level Classroom Award Description", $campaignAwardTypeClassroom, $campaignAwardStylePlace, 500, null);

      //Student Level Awards
      $this->createCampaignAward($campaign, "First Level Student Award", "First Level Student Award Description", $campaignAwardTypeStudent, $campaignAwardStylePlace, 50, null);
      $this->createCampaignAward($campaign, "Second Level Student Award", "Second Level Student Award Description", $campaignAwardTypeStudent, $campaignAwardStylePlace, 100, null);
      $this->createCampaignAward($campaign, "Third Level Student Award", "Third Level Student Award Description", $campaignAwardTypeStudent, $campaignAwardStylePlace, 500, null);



      /*
      * CREATE GRADES/TEACHERS/STUDENTS AWARDS
      */

      //Kindergarten
      $grade = $this->createGrade($campaign, 'Kindergarten');
        $classroom = $this->createClassroom($campaign, $grade, 'Mr. Willis');
          $student = $this->createStudent($campaign, $grade, $classroom, "John B.");
          $student = $this->createStudent($campaign, $grade, $classroom, "Chris E.");
          $student = $this->createStudent($campaign, $grade, $classroom, "Eric F.");

        $classroom = $this->createClassroom($campaign, $grade, 'Mrs. Streep');
          $student = $this->createStudent($campaign, $grade, $classroom, "Richard I.");
          $student = $this->createStudent($campaign, $grade, $classroom, "Kelly E.");
          $student = $this->createStudent($campaign, $grade, $classroom, "Stephanie I.");

        $classroom = $this->createClassroom($campaign, $grade, 'Mrs. Hepburn');
          $student = $this->createStudent($campaign, $grade, $classroom, "Bellamy O.");
          $student = $this->createStudent($campaign, $grade, $classroom, "Rodger R.");
          $student = $this->createStudent($campaign, $grade, $classroom, "Casey K.");

      //1st Grade
      $grade = $this->createGrade($campaign, '1st Grade');
      $classroom = $this->createClassroom($campaign, $grade, 'Mrs. Jolie');
        $student = $this->createStudent($campaign, $grade, $classroom, "Andrew M.");
        $student = $this->createStudent($campaign, $grade, $classroom, "David C.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Eleanor R.");

      $classroom = $this->createClassroom($campaign, $grade, 'Mrs. Monroe');
        $student = $this->createStudent($campaign, $grade, $classroom, "Henry P.");
        $student = $this->createStudent($campaign, $grade, $classroom, "John B.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Eric F.");

      $classroom = $this->createClassroom($campaign, $grade, 'Mrs. Portman');
        $student = $this->createStudent($campaign, $grade, $classroom, "Barbara D.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Thomas C.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Joann R.");

      //2nd Grade
      $grade = $this->createGrade($campaign, '2nd Grade');
      $classroom = $this->createClassroom($campaign, $grade, 'Mr. Jakcson');
        $student = $this->createStudent($campaign, $grade, $classroom, "Jessie B.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Christian H.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Sophia M.");

      $classroom = $this->createClassroom($campaign, $grade, 'Mrs. Jones');
        $student = $this->createStudent($campaign, $grade, $classroom, "Trevoyce W.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Ryo P.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Isabelle W.");

      $classroom = $this->createClassroom($campaign, $grade, 'Mrs. Lawrence');
        $student = $this->createStudent($campaign, $grade, $classroom, "Rahlena S.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Yasmine V.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Jerry F.");


      //3rd Grade
      $grade = $this->createGrade($campaign, '3rd Grade');
      $classroom = $this->createClassroom($campaign, $grade, 'Mrs. Kidman');
        $student = $this->createStudent($campaign, $grade, $classroom, "Bradley B.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Aiden K.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Candy K.");

      $classroom = $this->createClassroom($campaign, $grade, 'Mrs. Theron');
        $student = $this->createStudent($campaign, $grade, $classroom, "Ellen E.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Richard H.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Gregory R.");

      $classroom = $this->createClassroom($campaign, $grade, 'Mrs. Blanchett');
        $student = $this->createStudent($campaign, $grade, $classroom, "Park L.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Gabrielle B.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Ethan S.");


      //4th Grade
      $grade = $this->createGrade($campaign, '4th Grade');
      $classroom = $this->createClassroom($campaign, $grade, 'Mr. Ryan');
        $student = $this->createStudent($campaign, $grade, $classroom, "Bishop R.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Juno T.");
        $student = $this->createStudent($campaign, $grade, $classroom, "John B.");

      $classroom = $this->createClassroom($campaign, $grade, 'Mrs. Taylor');
        $student = $this->createStudent($campaign, $grade, $classroom, "Alexis S.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Kevin O.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Bobbie B.");

      $classroom = $this->createClassroom($campaign, $grade, 'Mrs. Foster');
        $student = $this->createStudent($campaign, $grade, $classroom, "Hilary C.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Donald T.");
        $student = $this->createStudent($campaign, $grade, $classroom, "John K.");


      //5th Grade
      $grade = $this->createGrade($campaign, '5th Grade');
      $classroom = $this->createClassroom($campaign, $grade, 'Mrs. Paltrow');
        $student = $this->createStudent($campaign, $grade, $classroom, "George W.");
        $student = $this->createStudent($campaign, $grade, $classroom, "James M.");
        $student = $this->createStudent($campaign, $grade, $classroom, "William C.");

      $classroom = $this->createClassroom($campaign, $grade, 'Mr. Balboa');
        $student = $this->createStudent($campaign, $grade, $classroom, "Abraham L.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Barak O.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Andrew J.");

      $classroom = $this->createClassroom($campaign, $grade, 'Mrs. Roberts');
        $student = $this->createStudent($campaign, $grade, $classroom, "Andrew G.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Franklin R.");
        $student = $this->createStudent($campaign, $grade, $classroom, "Thomas J.");


      #Save Everything
      $em->flush();

      #Return the campaign to be used later
      return $campaign;
    }

    private function createCampaignAward(Campaign $campaign, $name, $description, Campaignawardtype $type, Campaignawardstyle $style, $amount = 0, $place = 0){
      $em = $this->getDoctrine()->getManager();

      $campaignaward = new Campaignaward();
      $campaignaward->setCampaign($campaign);
      $campaignaward->setName($name);
      $campaignaward->setDescription($description);
      $campaignaward->setCampaignawardtype($type);
      $campaignaward->setCampaignawardstyle($style);

      if(!is_null($place)){
        $campaignaward->setPlace($place);
      }
      if(!is_null($amount)){
        $campaignaward->setPlace($amount);
      }

      $em->persist($campaignaward);
    }


    private function createGrade(Campaign $campaign, $name){
      $em = $this->getDoctrine()->getManager();

      $grade = new Grade();
      $grade->setCampaign($campaign);
      $grade->setName($name);

      $em->persist($grade);
      return $grade;
    }


    private function createClassroom(Campaign $campaign, Grade $grade, $name){
      $em = $this->getDoctrine()->getManager();

      $classroom = new Classroom();
      $classroom->setGrade($grade);
      $classroom->setTeacherName($name);
      $classroom->setName($name.'s classroom');
      $classroom->setCampaign($campaign);

      //Save
      $em->persist($classroom);
      return $classroom;
    }


    private function createStudent(Campaign $campaign, Grade $grade, Classroom $classroom,  $name){
      $em = $this->getDoctrine()->getManager();

      $student = new Student();
      $student->setGrade($grade);
      $student->setClassroom($classroom);
      $student->setName($name);
      $student->setCampaign($campaign);

      //Save
      $em->persist($student);
      return $student;
    }

    private function generateRandomString($length = 10) {

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }


    private function generateRandomDate($min_date, $max_date) {
        /* Gets 2 dates as string, earlier and later date.
           Returns date in between them.
        */

        $min_epoch = strtotime($min_date);
        $max_epoch = strtotime($max_date);

        $rand_epoch = rand($min_epoch, $max_epoch);

        return new DateTime('Y-m-d H:i:s', $rand_epoch);
    }




}
