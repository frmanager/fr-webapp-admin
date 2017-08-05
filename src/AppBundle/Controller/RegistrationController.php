<?php

namespace AppBundle\Controller;

use AppBundle\Form\UserType;
use AppBundle\Entity\User;
use AppBundle\Entity\Campaign;
use AppBundle\Entity\Campaignawardtype;
use AppBundle\Entity\Campaignawardstyle;
use AppBundle\Entity\Campaignaward;
use AppBundle\Entity\CampaignUser;
use AppBundle\Entity\Teacher;
use AppBundle\Entity\Grade;
use AppBundle\Entity\Student;
use AppBundle\Entity\UserStatus;
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

            //$logger->debug(print_r($user));

            //TODO: Verify passwords match
            // 3) Encode the password (you could also do this via Doctrine listener)
            $password = $passwordEncoder->encodePassword($user, $user->getPassword());
            $user->setPassword($password);
            $user->setApiKey($password);
            $user->setCampaignManagerFlag(true);
            $user->setUsername($user->getEmail());
            $user->setEmailConfirmationCode(base64_encode(random_bytes(20)));
            $user->setEmailConfirmationCodeTimestamp(new \DateTime());
            //Get User Status
            $userStatus = $em->getRepository('AppBundle:UserStatus')->findOneByName('Registered');

            if(!empty($userStatus)){
              $user->setUserStatus($userStatus);
            }else{
              $logger->debug('UserStatus of Registered could not be found');
              //TODO: Create generic flash message so user can contact support;
            }

            // 4) save the User!
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);

            $campaign = $this->createCampaign($user);

            $this->authenticateUser($user);

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
      $campaign->setUrl($this->generateRandomString(12));
      $campaign->setEmail($user->getEmail());
      $campaign->setFundingGoal(10000);
      $campaign->setCreatedBy($user);

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
      $campaignAwardTypeTeacher = $em->getRepository('AppBundle:Campaignawardtype')->findOneByValue('teacher');
      $campaignAwardTypeStudent = $em->getRepository('AppBundle:Campaignawardtype')->findOneByValue('student');
      $campaignAwardStyleLevel = $em->getRepository('AppBundle:Campaignawardstyle')->findOneByValue('level');
      $campaignAwardStylePlace = $em->getRepository('AppBundle:Campaignawardstyle')->findOneByValue('place');

      //Teacher Place Awards
      $this->createCampaignAward($campaign, "First Place Teacher Award", "First Place Teacher Award Description", $campaignAwardTypeTeacher, $campaignAwardStylePlace, null, 1);
      $this->createCampaignAward($campaign, "Second Place Teacher Award", "Second Place Teacher Award Description", $campaignAwardTypeTeacher, $campaignAwardStylePlace, null, 2);
      $this->createCampaignAward($campaign, "Third Place Teacher Award", "Third Place Teacher Award Description", $campaignAwardTypeTeacher, $campaignAwardStylePlace, null, 3);

      //Student Place Awards
      $this->createCampaignAward($campaign, "First Place Student Award", "First Place Student Award Description", $campaignAwardTypeStudent, $campaignAwardStylePlace, null, 1);
      $this->createCampaignAward($campaign, "Second Place Student Award", "Second Place Student Award Description", $campaignAwardTypeStudent, $campaignAwardStylePlace, null, 2);
      $this->createCampaignAward($campaign, "Third Place Student Award", "Third Place Student Award Description", $campaignAwardTypeStudent, $campaignAwardStylePlace, null, 3);

      //Teacher Level Awards
      $this->createCampaignAward($campaign, "First Level Teacher Award", "First Level Teacher Award Description", $campaignAwardTypeTeacher, $campaignAwardStylePlace, 50, null);
      $this->createCampaignAward($campaign, "Second Level Teacher Award", "Second Level Teacher Award Description", $campaignAwardTypeTeacher, $campaignAwardStylePlace, 100, null);
      $this->createCampaignAward($campaign, "Third Level Teacher Award", "Third Level Teacher Award Description", $campaignAwardTypeTeacher, $campaignAwardStylePlace, 500, null);

      //Student Level Awards
      $this->createCampaignAward($campaign, "First Level Student Award", "First Level Student Award Description", $campaignAwardTypeStudent, $campaignAwardStylePlace, 50, null);
      $this->createCampaignAward($campaign, "Second Level Student Award", "Second Level Student Award Description", $campaignAwardTypeStudent, $campaignAwardStylePlace, 100, null);
      $this->createCampaignAward($campaign, "Third Level Student Award", "Third Level Student Award Description", $campaignAwardTypeStudent, $campaignAwardStylePlace, 500, null);



      /*
      * CREATE GRADES/TEACHERS/STUDENTS AWARDS
      */

      //Kindergarten
      $grade = $this->createGrade($campaign, 'Kindergarten');
        $teacher = $this->createTeacher($campaign, $grade, 'Mr. Willis');
          $student = $this->createStudent($campaign, $grade, $teacher, "John B.");
          $student = $this->createStudent($campaign, $grade, $teacher, "Chris E.");
          $student = $this->createStudent($campaign, $grade, $teacher, "Eric F.");

        $teacher = $this->createTeacher($campaign, $grade, 'Mrs. Streep');
          $student = $this->createStudent($campaign, $grade, $teacher, "Richard I.");
          $student = $this->createStudent($campaign, $grade, $teacher, "Kelly E.");
          $student = $this->createStudent($campaign, $grade, $teacher, "Stephanie I.");

        $teacher = $this->createTeacher($campaign, $grade, 'Mrs. Hepburn');
          $student = $this->createStudent($campaign, $grade, $teacher, "Bellamy O.");
          $student = $this->createStudent($campaign, $grade, $teacher, "Rodger R.");
          $student = $this->createStudent($campaign, $grade, $teacher, "Casey K.");

      //1st Grade
      $grade = $this->createGrade($campaign, '1st Grade');
      $teacher = $this->createTeacher($campaign, $grade, 'Mrs. Jolie');
        $student = $this->createStudent($campaign, $grade, $teacher, "Andrew M.");
        $student = $this->createStudent($campaign, $grade, $teacher, "David C.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Eleanor R.");

      $teacher = $this->createTeacher($campaign, $grade, 'Mrs. Monroe');
        $student = $this->createStudent($campaign, $grade, $teacher, "Henry P.");
        $student = $this->createStudent($campaign, $grade, $teacher, "John B.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Eric F.");

      $teacher = $this->createTeacher($campaign, $grade, 'Mrs. Portman');
        $student = $this->createStudent($campaign, $grade, $teacher, "Barbara D.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Thomas C.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Joann R.");

      //2nd Grade
      $grade = $this->createGrade($campaign, '2nd Grade');
      $teacher = $this->createTeacher($campaign, $grade, 'Mr. Jakcson');
        $student = $this->createStudent($campaign, $grade, $teacher, "Jessie B.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Christian H.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Sophia M.");

      $teacher = $this->createTeacher($campaign, $grade, 'Mrs. Jones');
        $student = $this->createStudent($campaign, $grade, $teacher, "Trevoyce W.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Ryo P.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Isabelle W.");

      $teacher = $this->createTeacher($campaign, $grade, 'Mrs. Lawrence');
        $student = $this->createStudent($campaign, $grade, $teacher, "Rahlena S.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Yasmine V.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Jerry F.");


      //3rd Grade
      $grade = $this->createGrade($campaign, '3rd Grade');
      $teacher = $this->createTeacher($campaign, $grade, 'Mrs. Kidman');
        $student = $this->createStudent($campaign, $grade, $teacher, "Bradley B.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Aiden K.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Candy K.");

      $teacher = $this->createTeacher($campaign, $grade, 'Mrs. Theron');
        $student = $this->createStudent($campaign, $grade, $teacher, "Ellen E.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Richard H.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Gregory R.");

      $teacher = $this->createTeacher($campaign, $grade, 'Mrs. Blanchett');
        $student = $this->createStudent($campaign, $grade, $teacher, "Park L.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Gabrielle B.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Ethan S.");


      //4th Grade
      $grade = $this->createGrade($campaign, '4th Grade');
      $teacher = $this->createTeacher($campaign, $grade, 'Mr. Ryan');
        $student = $this->createStudent($campaign, $grade, $teacher, "Bishop R.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Juno T.");
        $student = $this->createStudent($campaign, $grade, $teacher, "John B.");

      $teacher = $this->createTeacher($campaign, $grade, 'Mrs. Taylor');
        $student = $this->createStudent($campaign, $grade, $teacher, "Alexis S.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Kevin O.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Bobbie B.");

      $teacher = $this->createTeacher($campaign, $grade, 'Mrs. Foster');
        $student = $this->createStudent($campaign, $grade, $teacher, "Hilary C.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Donald T.");
        $student = $this->createStudent($campaign, $grade, $teacher, "John K.");


      //5th Grade
      $grade = $this->createGrade($campaign, '5th Grade');
      $teacher = $this->createTeacher($campaign, $grade, 'Mrs. Paltrow');
        $student = $this->createStudent($campaign, $grade, $teacher, "George W.");
        $student = $this->createStudent($campaign, $grade, $teacher, "James M.");
        $student = $this->createStudent($campaign, $grade, $teacher, "William C.");

      $teacher = $this->createTeacher($campaign, $grade, 'Mr. Balboa');
        $student = $this->createStudent($campaign, $grade, $teacher, "Abraham L.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Barak O.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Andrew J.");

      $teacher = $this->createTeacher($campaign, $grade, 'Mrs. Roberts');
        $student = $this->createStudent($campaign, $grade, $teacher, "Andrew G.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Franklin R.");
        $student = $this->createStudent($campaign, $grade, $teacher, "Thomas J.");


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


    private function createTeacher(Campaign $campaign, Grade $grade, $name){
      $em = $this->getDoctrine()->getManager();

      $teacher = new Teacher();
      $teacher->setGrade($grade);
      $teacher->setTeacherName($name);
      $teacher->setCampaign($campaign);

      //Save
      $em->persist($teacher);
      return $teacher;
    }


    private function createStudent(Campaign $campaign, Grade $grade, Teacher $teacher,  $name){
      $em = $this->getDoctrine()->getManager();

      $student = new Student();
      $student->setGrade($grade);
      $student->setTeacher($teacher);
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
