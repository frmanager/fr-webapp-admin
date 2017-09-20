<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Team;
use AppBundle\Entity\Grade;
use AppBundle\Utils\ValidationHelper;
use AppBundle\Utils\CSVHelper;
use AppBundle\Utils\CampaignHelper;
use AppBundle\Utils\QueryHelper;
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
  public function teamIndexAction($campaignUrl)
  {
      $logger = $this->get('logger');
      $entity = 'Team';
      $em = $this->getDoctrine()->getManager();

      //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
      $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
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
        'teams' => $em->getRepository('AppBundle:Team')->findByCampaign($campaign),
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
    public function showAction($campaignUrl, $teamUrl)
    {
        $logger = $this->get('logger');
        $entity = 'Team';
        $em = $this->getDoctrine()->getManager();

        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
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
        $team = $em->getRepository('AppBundle:Team')->findOneBy(array('url'=>$teamUrl, 'campaign' => $campaign));
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
    public function editAction(Request $request, $campaignUrl, $teamUrl)
    {
        $logger = $this->get('logger');
        $entity = 'Team';
        $this->denyAccessUnlessGranted('ROLE_USER');

        $em = $this->getDoctrine()->getManager();

        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
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
        $team = $em->getRepository('AppBundle:Team')->findOneBy(array('url'=>$teamUrl, 'campaign' => $campaign));
        if(is_null($team)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this team.');
          return $this->redirectToRoute('team_index', array('campaignUrl'=>$campaign->getUrl()));
        }

        //CODE TO CHECK TO SEE IF THIS CAMPAIGN IS MANAGED BY THIS USER
        if($team->getUser()->getId() !== $this->get('security.token_storage')->getToken()->getUser()->getId()){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, you cannot edit this team');
          return $this->redirectToRoute('team_show', array('campaignUrl'=>$campaign->getUrl(), 'teamUrl' => $team->getUrl()));
        }

        if ($request->isMethod('POST')) {
            $params = $request->request->all();
        }

        return $this->render('team/team.edit.html.twig', array(
            'team' => $team,
            'campaign' => $campaign,
        ));
    }




    /**
     * Displays a form to edit an existing Team entity.
     *
     * @Route("/{teamUrl}/verify_student/{teamStudentId}", name="teamStudent_verify")
     * @Method({"GET", "POST"})
     */
    public function verifyTeamStudentAction(Request $request, $campaignUrl, $teamUrl, $teamStudentId)
    {
        $logger = $this->get('logger');
        $entity = 'Team';
        $this->denyAccessUnlessGranted('ROLE_USER');

        $em = $this->getDoctrine()->getManager();

        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
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
        $team = $em->getRepository('AppBundle:Team')->findOneBy(array('url'=>$teamUrl, 'campaign' => $campaign));
        if(is_null($team)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this team.');
          return $this->redirectToRoute('team_index', array('campaignUrl'=>$campaign->getUrl()));
        }

        //CODE TO CHECK TO SEE IF TEAMSTUDENT EXISTS
        $teamStudent = $em->getRepository('AppBundle:TeamStudent')->find($teamStudentId);
        if(is_null($teamStudent)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this team.');
          return $this->redirectToRoute('team_show', array('campaignUrl'=>$campaign->getUrl(), 'teamUrl'=>$team->getUrl()));
        }


        if(null !== $request->query->get('action') && null !== $request->query->get('studentID')){
            $failure = false;
            $studentID = $request->query->get('studentID');
            $logger->debug("Linking TeamStudent #".$teamStudent->getId()." with student ".$studentID);

            $student = $em->getRepository('AppBundle:Student')->find($studentID);
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


}
