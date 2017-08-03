<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Grade;
use AppBundle\Entity\Campaign;

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
        $em = $this->getDoctrine()->getManager();

        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
        if(is_null($campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'Campaign does not exist.');
          return $this->redirectToRoute('homepage');
        }

        if(!$this->campaignPermissionsCheck($campaign)){
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

        return $this->render('campaign/grade.index.html.twig', array(
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
        $entity = 'Grade';
        $grade = new Grade();
        $em = $this->getDoctrine()->getManager();

        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
        if(is_null($campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'Campaign does not exist.');
          return $this->redirectToRoute('homepage');
        }

        if(!$this->campaignPermissionsCheck($campaign)){
            $this->get('session')->getFlashBag()->add('warning', 'You do not have permissions to this campaign.');
            return $this->redirectToRoute('homepage');
        }

        $form = $this->createForm('AppBundle\Form\GradeType', $grade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($grade);
            $em->flush();

            return $this->redirectToRoute('grade_index', array('campaignUrl' => $campaignUrl, 'id' => $grade->getId()));
        }

        return $this->render('crud/new.html.twig', array(
            'grade' => $grade,
            'form' => $form->createView(),
            'entity' => $entity,
            'campaign' => $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl),
        ));
    }

    /**
     * Finds and displays a Grade entity.
     *
     * @Route("/{id}", name="grade_show")
     * @Method("GET")
     */
    public function showAction(Grade $grade, $campaignUrl)
    {
        $entity = 'Grade';
        $deleteForm = $this->createDeleteForm($grade, $campaignUrl);
        $em = $this->getDoctrine()->getManager();

        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
        if(is_null($campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'Campaign does not exist.');
          return $this->redirectToRoute('homepage');
        }

        if(!$this->campaignPermissionsCheck($campaign)){
            $this->get('session')->getFlashBag()->add('warning', 'You do not have permissions to this campaign.');
            return $this->redirectToRoute('homepage');
        }


        return $this->render('campaign/grade.show.html.twig', array(
            'grade' => $grade,
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
            'campaign' => $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl)
        ));
    }

    /**
     * Displays a form to edit an existing Grade entity.
     *
     * @Route("/edit/{id}", name="grade_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Grade $grade, $campaignUrl)
    {
        $entity = 'Grade';
        $em = $this->getDoctrine()->getManager();

        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
        if(is_null($campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'Campaign does not exist.');
          return $this->redirectToRoute('homepage');
        }

        if(!$this->campaignPermissionsCheck($campaign)){
            $this->get('session')->getFlashBag()->add('warning', 'You do not have permissions to this campaign.');
            return $this->redirectToRoute('homepage');
        }


        $deleteForm = $this->createDeleteForm($grade, $campaignUrl);
        $editForm = $this->createForm('AppBundle\Form\GradeType', $grade);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($grade);
            $em->flush();

            return $this->redirectToRoute('grade_index', array('campaignUrl'=> $campaignUrl, 'id' => $grade->getId()));
        }

        return $this->render('crud/edit.html.twig', array(
            'grade' => $grade,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
            'campaign' => $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl),
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
        $em = $this->getDoctrine()->getManager();

        //CODE TO CHECK TO SEE IF CAMPAIGN EXISTS
        $campaign = $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl);
        if(is_null($campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'Campaign does not exist.');
          return $this->redirectToRoute('homepage');
        }

        if(!$this->campaignPermissionsCheck($campaign)){
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



    /**
    *
    * campaignPermissionsCheck takes the campaign that was requested and verifies user has access to it
    *
    * Access is verified by looking at the CampaignUser entity and verifying a record exists
    * for that campaign and user combination
    *
    * @param Campaign $campaign
    *
    * @return boolean
    *
    */
    private function campaignPermissionsCheck(Campaign $campaign){
      $em = $this->getDoctrine()->getManager();

      //CODE TO PROTECT CONTROLLER FROM USERS WHO ARE NOT IN CAMPAIGNUSER TABLE
      //TODO: ADD CODE TO ALLOW ADMINS TO ACCESS
      $query = $em->createQuery('SELECT IDENTITY(cu.campaign) FROM AppBundle:CampaignUser cu where cu.user=?1');
      $query->setParameter(1, $this->get('security.token_storage')->getToken()->getUser());
      $results = array_map('current', $query->getScalarResult());
      if(!in_array($campaign->getId(), $results)){
        return false;
      }
      return true;
    }

}
