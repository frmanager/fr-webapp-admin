<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Team;
use App\Entity\Grade;
use App\Entity\Student;
use App\Entity\TeamStudent ;
use App\Utils\ValidationHelper;
use App\Utils\CSVHelper;
use App\Utils\CampaignHelper;
use App\Utils\QueryHelper;
use App\Utils\DonationHelper;
use DateTime;

/**
 * Team controller.
 *
 * @Route("/{campaignUrl}/team")
 */
class TeamController extends Controller
{
  /**
   * Lists all Team entities.
   *
   * @Route("/", name="team_index")
   * @Method({"GET", "POST"})
   */
  public function teamIndexAction($campaignUrl, LoggerInterface $logger)
  {
      
      $entity = 'Team';
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

      // replace this example code with whatever you need
      return $this->render('team/team.index.html.twig', array(
        'teams' => $em->getRepository('App:Team')->findByCampaign($campaign),
        'entity' => strtolower($entity),
        'campaign' => $campaign,
      ));

  }

    /**
     * Finds and displays a Team entity.
     *
     * @Route("/{teamUrl}", name="team_show")
     * @Method("GET")
     */
    public function showAction($campaignUrl, $teamUrl, LoggerInterface $logger)
    {
        
        $entity = 'Team';
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


        //CODE TO CHECK TO SEE IF TEAM EXISTS
        $team = $em->getRepository('App:Team')->findOneBy(array('url'=>$teamUrl, 'campaign' => $campaign));
        if(is_null($team)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this team.');
          return $this->redirectToRoute('team_index', array('campaignUrl'=>$campaign->getUrl()));
        }

        return $this->render('team/team.show.html.twig', array(
            'team' => $team,
            'entity' => $entity,
            'campaign' => $campaign,
            'teamStudents' => $team->getTeamStudents(),
        ));
    }


    /**
     * Displays a form to edit an existing Team entity.
     *
     * @Route("/{teamUrl}/edit", name="team_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, $campaignUrl, $teamUrl, LoggerInterface $logger)
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

        //CODE TO CHECK TO SEE IF TEAM EXISTS
        $team = $em->getRepository('App:Team')->findOneBy(array('url'=>$teamUrl, 'campaign' => $campaign));
        if(is_null($team)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this team.');
          return $this->redirectToRoute('team_index', array('campaignUrl'=>$campaign->getUrl()));
        }


        if(null !== $request->query->get('action')){
            $action = $request->query->get('action');

            if($action === 'delete_team_student'){
              $logger->debug("Performing delete_team_student");
              if(null == $request->query->get('teamStudentID')){
                $this->get('session')->getFlashBag()->add('warning', 'Could not delete student, ID not provided');
                return $this->redirectToRoute('team_edit', array('campaignUrl' => $campaign->getUrl(), 'teamUrl' => $teamUrl));
              }

              $teamStudent = $em->getRepository('App:TeamStudent')->find($request->query->get('teamStudentID'));
              if(empty($teamStudent)){
                $this->get('session')->getFlashBag()->add('warning', 'Could not find student to delete');
                return $this->redirectToRoute('team_edit', array('campaignUrl' => $campaign->getUrl(), 'teamUrl' => $teamUrl));
              }

              $logger->debug("Removing TeamStudent #".$teamStudent->getId());
              $em->remove($teamStudent);
              $logger->debug("Flushing");
              $em->flush();

              $this->get('session')->getFlashBag()->add('info', 'Student has been removed');
              return $this->redirectToRoute('team_edit', array('campaignUrl' => $campaign->getUrl(), 'teamUrl' => $teamUrl));
            }
        }



        if ($request->isMethod('POST')) {
            $params = $request->request->all();
            $logger->debug('Request Params: '.print_r($params, true));
            $fail = false;
            //dump($request->request->get('data'));
            //$logger->debug('Team Name: '.print_r($params, true));

            //form Validation
            if(empty($params['team']['name'])){
              $this->addFlash('warning','Family name is required');
              $fail = true;
            }

            if(empty($params['team']['fundingGoal'])){
              $this->addFlash('warning','Funding goal is required');
              $fail = true;
            }

            if($this->reservedWordCheck($params['team']['name'])){
              $this->addFlash('warning','We apologize, this name cannot be used');
              $fail = true;
            }

            if($this->badWordCheck($params['team']['name'])){
              $this->addFlash('warning','We apologize, this name cannot be used');
              $fail = true;
            }

            //If it is a student team, a class and student name is required
            if($team->getTeamType()->getValue() == "student"){
              if(empty($params['team']['student']['classroomID']) or empty($params['team']['student']['name']) or $params['team']['student']['classroomID'] == '' or $params['team']['student']['name'] == ''){
                $this->addFlash('warning','Student classrom and name is required');
                $fail = true;
              }
            }

            //If it is a teacher team a class is required
            if($team->getTeamType()->getValue() == "teacher"){
              if(empty($params['team']['classroom']['classroomID']) or $params['team']['classroom']['classroomID'] == ''){
                $this->addFlash('warning','Please select a classroom');
                $fail = true;
              }
            }


            if(!$fail){
              $logger->debug("Updating team: ".$team->getName());

              if(!empty($request->files->get('team')) && !is_null($request->files->get('team')['image'])){
                  $logger->debug("Updating team photo");
                  $file = $request->files->get('team');

                  // Generate a unique name for the file before saving it
                  $fileName = md5(uniqid());
                  $fileNameWithExtension = $fileName.'.'.$file['image']->guessExtension();

                  // Move the file to the directory where brochures are stored
                  $file['image']->move(
                      $this->getParameter('temp_upload_directory'),
                      $fileNameWithExtension
                  );

                  $image_array = getimagesize($this->getParameter('temp_upload_directory').'/'.$fileNameWithExtension);
                  $mime_type = $image_array['mime'];
                  $logger->debug("Image mime_type is: ".$mime_type);
                  list($widthOriginal, $heightOriginal) = getimagesize($this->getParameter('temp_upload_directory').'/'.$fileNameWithExtension);
                  $maxWidth = 800;
                  $maxHeight = 600;
                  $resizeRatio = 1;
                  //setting resizing Ratio
                  //first we figure out of this is profile or portrait
                  if($widthOriginal > $heightOriginal){
                    $resizeRatio = round($maxWidth/$widthOriginal, 2);
                  }elseif($heightOriginal > $widthOriginal){
                    $resizeRatio = round($maxHeight/$heightOriginal, 2);
                  }

                  if($mime_type == 'image/jpeg'){
                    $imageObject = imagecreatefromjpeg($this->getParameter('temp_upload_directory').'/'.$fileNameWithExtension);
                  }elseif($mime_type == 'image/png'){
                    $imageObject = imagecreatefrompng($this->getParameter('temp_upload_directory').'/'.$fileNameWithExtension);
                  }elseif($mime_type == 'image/gif'){
                    $imageObject = imagecreatefromgif($this->getParameter('temp_upload_directory').'/'.$fileNameWithExtension);
                  }

                  $desginationImageObject = imagecreatetruecolor(($widthOriginal*$resizeRatio), ($heightOriginal*$resizeRatio));
                  imagecopyresampled($desginationImageObject, $imageObject, 0, 0, 0, 0, ($widthOriginal*$resizeRatio), ($heightOriginal*$resizeRatio), $widthOriginal, $heightOriginal);
                  imagepng($desginationImageObject, $this->getParameter('team_profile_photos_directory').'/'.$fileName.'.png');
                  imagedestroy($imageObject);
                  imagedestroy($desginationImageObject);


                  //Delete Temp Photo
                  unlink($this->getParameter('temp_upload_directory').'/'.$fileNameWithExtension);
                  //Delete Old Photo (If existed)
                  if(!empty($team->getImageName()) && $team->getImageName() !== ""){
                    unlink($this->getParameter('team_profile_photos_directory').'/'.$team->getImageName());
                  }
                  $team->setImageName($fileName.'.png');
              }

              if($team->getName() !== $params['team']['name']){
                $team->setName($params['team']['name']);
                $team->setUrl($this->createTeamUrl($campaign, $params['team']['name']));
              }
              $team->setFundingGoal($params['team']['fundingGoal']);
              $team->setDescription($params['team']['description']);


              //If it is a "Teacher" team, set the classroom
              if($team->getTeamType()->getValue() == "teacher"){
                  $team->setClassroom($em->getRepository('App:Classroom')->find($params['team']['classroom']['classroomID']));
              }

              $em->persist($team);
              $em->flush();

              $user = $em->getRepository('App:User')->find($this->get('security.token_storage')->getToken()->getUser()->getId());
              $user->setFundraiserFlag(true);
              $em->persist($user);
              $em->flush();

              //If is a "family" page, we need to add students
              if($team->getTeamType()->getValue() == "family"){
                if(!empty($params['team']['students'])){
                  foreach ($params['team']['students'] as $key => $student) {
                    $teamStudent = $em->getRepository('App:TeamStudent')->find($student['id']);
                    if(!empty($teamStudent)){
                      if($teamStudent->getClassroom()->getId() !== $student['classroomID'] || $teamStudent->getName() !== $student['name']){
                        $teamStudent->setClassroom($em->getRepository('App:Classroom')->find($student['classroomID']));
                        $teamStudent->setGrade($em->getRepository('App:Grade')->find($teamStudent->getClassroom()->getGrade()));
                        $teamStudent->setName($student['name']);
                        $em->persist($teamStudent);
                      }
                    }else{
                      $logger->info("Could not find TeamStudent #".$student['id']);
                    }
                  }
                }

                  if(!empty($params['team']['newStudent'])){
                    $student = $params['team']['newStudent'];
                    if(!empty($student['classroomID']) && !empty($student['name'])){
                      $logger->debug("Adding TeamStudents to Team ".$team->getId());
                      $teamStudent = new TeamStudent();
                      $teamStudent->setTeam($team);
                      $teamStudent->setClassroom($em->getRepository('App:Classroom')->find($student['classroomID']));
                      $teamStudent->setGrade($em->getRepository('App:Grade')->find($teamStudent->getClassroom()->getGrade()));
                      $teamStudent->setName($student['name']);
                      $teamStudent->setCreatedBy($this->get('security.token_storage')->getToken()->getUser());
                      $em->persist($teamStudent);
                    }
                  }

                }else if($team->getTeamType()->getValue() == "student"){
                  $student = $params['team']['student'];
                  $logger->debug("Adding TeamStudents".$team->getId());
                  $teamStudent = $em->getRepository('App:TeamStudent')->findOneBy(array('team'=>$team));
                  if(empty($teamStudent)){
                    $teamStudent = new TeamStudent();
                  }
                  $teamStudent->setTeam($team);
                  $teamStudent->setClassroom($em->getRepository('App:Classroom')->find($student['classroomID']));
                  $teamStudent->setGrade($em->getRepository('App:Grade')->find($teamStudent->getClassroom()->getGrade()));
                  $teamStudent->setName($student['name']);
                  $teamStudent->setCreatedBy($this->get('security.token_storage')->getToken()->getUser());
                  $em->persist($teamStudent);
                }

              $em->flush();

              $this->addFlash('success','Team has been updated!');
              return $this->redirectToRoute('team_show', array('campaignUrl' => $campaign->getUrl(), 'teamUrl' => $team->getUrl()));
            }

        }

        $qb = $em->createQueryBuilder()->select('u')
             ->from('App:Classroom', 'u')
             ->join('App:Grade', 'g')
             ->where('u.grade = g.id')
             ->andWhere('u.campaign = :campaignID')
             ->setParameter('campaignID', $campaign->getId())
             ->orderBy('g.name', 'ASC');

        $classrooms =  $qb->getQuery()->getResult();

        return $this->render('team/team.edit.html.twig', array(
            'team' => $team,
            'campaign' => $campaign,
            'classrooms' => $classrooms,
        ));
    }



    /**
     * Displays a form to edit an existing Team entity.
     *
     * @Route("/{teamUrl}/verify_student/{teamStudentId}", name="teamStudent_verify")
     * @Method({"GET", "POST"})
     */
    public function verifyTeamStudentAction(Request $request, $campaignUrl, $teamUrl, $teamStudentId, LoggerInterface $logger)
    {
        
        $entity = 'Team';
        $this->denyAccessUnlessGranted('ROLE_USER');

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

        //CODE TO CHECK TO SEE IF TEAM EXISTS
        $team = $em->getRepository('App:Team')->findOneBy(array('url'=>$teamUrl, 'campaign' => $campaign));
        if(is_null($team)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this team.');
          return $this->redirectToRoute('team_index', array('campaignUrl'=>$campaign->getUrl()));
        }

        //CODE TO CHECK TO SEE IF TEAMSTUDENT EXISTS
        $teamStudent = $em->getRepository('App:TeamStudent')->find($teamStudentId);
        if(is_null($teamStudent)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this team.');
          return $this->redirectToRoute('team_show', array('campaignUrl'=>$campaign->getUrl(), 'teamUrl'=>$team->getUrl()));
        }


        if(null !== $request->query->get('action') && null !== $request->query->get('studentID')){
            $failure = false;
            $studentID = $request->query->get('studentID');
            $logger->debug("Linking TeamStudent #".$teamStudent->getId()." with student ".$studentID);

            $student = $em->getRepository('App:Student')->find($studentID);
            if(is_null($student)){
              $logger->debug("Could not find Student");
              $this->get('session')->getFlashBag()->add('warning', 'We are sorry, There was an issue adding that student.');
              $failure = true;
            }

            if(!$failure){
              $teamStudent->setStudent($student);
              $teamStudent->setConfirmedFlag(true);
              $em->persist($teamStudent);
              $em->flush();
              return $this->redirectToRoute('team_show', array('campaignUrl'=>$campaign->getUrl(), 'teamUrl'=>$team->getUrl()));
            }
        }

        return $this->render('team/team.verify.html.twig', array(
            'students' => $teamStudent->getClassroom()->getStudents(),
            'team' => $team,
            'classroom' => $teamStudent->getClassroom(),
            'teamStudent' => $teamStudent,
            'campaign' => $campaign,
        ));
    }


    /**
     * Reassignes a team Entity.
     *
     * @Route("/{teamUrl}/reassign", name="team_reassign")
     * @Method({"GET", "POST"})
     */
    public function reassignAction(Request $request, $campaignUrl, $teamUrl, LoggerInterface $logger)
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


      //CODE TO CHECK TO SEE IF TEAM EXISTS
      $team = $em->getRepository('App:Team')->findOneBy(array('url'=>$teamUrl, 'campaign' => $campaign));
      if(is_null($team)){
        $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this team.');
        return $this->redirectToRoute('team_index', array('campaignUrl'=>$campaign->getUrl()));
      }


      if(null !== $request->query->get('type')){
          $type = $request->query->get('type');

          if(!in_array($type, array('family','student','teacher'))){
            $this->get('session')->getFlashBag()->add('warning', $type.' is not a valid team type');
            return $this->redirectToRoute('team_show', array('campaignUrl'=>$campaign->getUrl(), 'teamUrl' => $team->getUrl()));
          }

      }else{
        $this->get('session')->getFlashBag()->add('warning', 'You need to select a team type');
        return $this->redirectToRoute('team_show', array('campaignUrl'=>$campaign->getUrl(), 'teamUrl' => $team->getUrl()));
      }

      if($team->getTeamType()->getValue() == $type){
        $this->get('session')->getFlashBag()->add('warning', 'Your team is already a "'.$team->getType()->getName().'" team');
        return $this->redirectToRoute('team_show', array('campaignUrl'=>$campaign->getUrl(), 'teamUrl' => $team->getUrl()));
      }

      $newType = $em->getRepository('App:TeamType')->findOneByValue($type);
      $team->setTeamType($newType);

      if($type == "teacher" || $type == "student"){
        $teamStudents = $em->getRepository('App:TeamStudent')->findByTeam($team);
        foreach ($teamStudents as $teamStudent) {
          $em->remove($teamStudent);
        }
      }

      $em->persist($team);
      $em->flush();

      $this->get('session')->getFlashBag()->add('success', 'Team has been successfully switched');
      return $this->redirectToRoute('team_show', array('campaignUrl'=>$campaign->getUrl(), 'teamUrl' => $team->getUrl()));
    }












    /*
    *
    * Returns @true if it finds a reserved word
    * Returns @false if it does not find a reserved word
    *
    */
    private function reservedWordCheck($name){

      //Matches entire string
      if(in_array($name, array('new', 'register', 'family'))){
        return true;
      }

      //Looks for individual words
      /*
      if (strpos($name, 'are') !== false) {
          echo 'true';
      }
      */

      return false;
    }


    private function badWordCheck($string){
      $badWords = array("fuck","fucker","bullshit","sex","shit","damn","ass","fart","cunt","bitch","nigger","pussy","piss","cock","turd");

      $matches = array();
      $matchFound = preg_match_all(
                      "/\b(" . implode($badWords,"|") . ")\b/i",
                      $string,
                      $matches
                    );

      if ($matchFound) {
        return true;
      }

      return false;
    }


    private function createTeamUrl(Campaign $campaign, $teamName){
      $em = $this->getDoctrine()->getManager();
      //this logic looks to see if URL is in use and then iterates on it
      $newUrl = preg_replace("/[^ \w]+/", "", $teamName);
      $newUrl = str_replace(' ', '-', strtolower($newUrl));
      $teamCheck = $em->getRepository('App:Team')->findOneBy(array('url' => $newUrl, 'campaign' => $campaign));
      if(!empty($teamCheck)){
        $fixed = false;
        $count = 1;
        while(!$fixed){
          $teamCheck = $em->getRepository('App:Team')->findOneBy(array('url' => $newUrl.$count, 'campaign' => $campaign));
          if(empty($teamCheck)){
            $newUrl = $newUrl.$count;
            $fixed = true;
          }else{
            $count ++;
          }
        }
      }
      return $newUrl;
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

}
