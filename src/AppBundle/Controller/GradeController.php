<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Grade;
use AppBundle\Entity\Campaign;
use AppBundle\Utils\CampaignHelper;
use DateTime;

/**
 * Manage Grade controller.
 *
 * @Route("/{campaignUrl}/grades")
 */
class GradeController extends Controller
{
    /**
     * Lists all Grade entities.
     *
     * @Route("/", name="grade_index")
     * @Method("GET")
     */
    public function indexAction($campaignUrl)
    {
        $entity = 'Grade';
        $logger = $this->get('logger');
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


        $grades = $em->getRepository('AppBundle:Grade')->findByCampaign($campaign);

        if (empty($grades)) {
            $defaultGrades = ['Kindergarten', '1st Grade', '2nd Grade', '3rd Grade', '4th Grade', '5th Grade', 'ID', 'ED'];
            foreach ($defaultGrades as $defaultGrade) {
                $em = $this->getDoctrine()->getManager();

                $grade = new Grade();
                $grade->setName($defaultGrade);
                $grade->setCampaign($campaign);
                $em->persist($grade);
                $em->flush();
            }
            $em->clear();

            $grades = $em->getRepository('AppBundle:Grade')->findByCampaign($campaign);

            $this->addFlash(
              'info',
              'Default Grades Added'
            );
        }

        return $this->render('grade/grade.index.html.twig', array(
            'grades' => $grades,
            'entity' => $entity,
            'campaign' => $campaign,
        ));
    }

    /**
     * Creates a new Grade entity.
     *
     * @Route("/new", name="grade_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, $campaignUrl)
    {
        $logger = $this->get('logger');
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

        $grade = new Grade();

        if ($request->isMethod('POST')) {
            $params = $request->request->all();
            $fail = false;

            if(!$fail && empty($params['grade']['name'])){
              $this->addFlash('warning','Grade name is required');
              $fail = true;
            }


            if(!$fail){

              $grade->setName($params['grade']['name']);
              $grade->setCampaign($campaign);

              $em->persist($grade);
              $em->flush();
              return $this->redirectToRoute('grade_index', array('campaignUrl'=> $campaignUrl));

            }

        }

        return $this->render('grade/grade.form.html.twig', array(
            'grade' => $grade,
            'campaign' => $campaign,
        ));
    }


    /**
     * Displays a form to edit an existing Grade entity.
     *
     * @Route("/edit/{gradeId}", name="grade_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, $campaignUrl, $gradeId)
    {
        $logger = $this->get('logger');
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

        //CODE TO CHECK TO SEE IF GRADE EXISTS
        $grade = $em->getRepository('AppBundle:Grade')->find($gradeId);
        if(is_null($grade)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this grade.');
          return $this->redirectToRoute('homepage');
        }


        if ($request->isMethod('POST')) {
            $params = $request->request->all();
            $fail = false;

            if(!$fail && empty($params['grade']['name'])){
              $this->addFlash('warning','Grade name is required');
              $fail = true;
            }

            if(!$fail){

              $grade->setName($params['grade']['name']);
              $grade->setCampaign($campaign);

              $em->persist($grade);
              $em->flush();
              return $this->redirectToRoute('grade_index', array('campaignUrl'=> $campaignUrl));

            }
        }

        return $this->render('grade/grade.form.html.twig', array(
            'grade' => $grade,
            'campaign' => $campaign,
        ));
    }

    /**
     * Deletes a Grade entity.
     *
     * @Route("/delete/{id}", name="grade_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Grade $grade, $campaignUrl)
    {
        $entity = 'Grade';
        $logger = $this->get('logger');
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


        $form = $this->createDeleteForm($grade, $campaignUrl);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($grade);
            $em->flush();
        }

        return $this->redirectToRoute('grade_index');
    }

    /**
     * Creates a form to delete a Grade entity.
     *
     * @param Grade $grade The Grade entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Grade $grade, $campaignUrl)
    {
        $entity = 'Grade';

        return $this->createFormBuilder()
            ->setAction($this->generateUrl('grade_delete', array('campaignUrl'=> $campaignUrl, 'id' => $grade->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    private function clean($string)
    {
        $string = str_replace(' ', '_', $string); // Replaces all spaces with underscores.
   $string = preg_replace('/[^A-Za-z0-9\_]/', '', $string); // Removes special chars.
   return strtolower($string);
    }

}
