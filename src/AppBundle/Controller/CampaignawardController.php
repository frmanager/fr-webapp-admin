<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Campaignaward;

/**
 * Campaignaward controller.
 *
 * @Route("/manage/campaignaward")
 */
class CampaignawardController extends Controller
{
    /**
     * Lists all Campaignaward entities.
     *
     * @Route("/", name="campaignaward_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $entity = 'Campaignaward';
        $em = $this->getDoctrine()->getManager();

        $campaignawards = $em->getRepository('AppBundle:Campaignaward')->findAll();

        return $this->render(strtolower($entity).'/index.html.twig', array(
            'campaignawards' => $campaignawards,
            'entity' => $entity,
        ));
    }

    /**
     * Creates a new Campaignaward entity.
     *
     * @Route("/new", name="campaignaward_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $entity = 'Campaignaward';
        $campaignaward = new Campaignaward();
        $form = $this->createForm('AppBundle\Form\CampaignawardType', $campaignaward);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($campaignaward);
            $em->flush();

            return $this->redirectToRoute('campaignaward_index', array('id' => $campaignaward->getId()));
        }

        return $this->render('crud/new.html.twig', array(
            'campaignaward' => $campaignaward,
            'form' => $form->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Finds and displays a Campaignaward entity.
     *
     * @Route("/show/{id}", name="campaignaward_show")
     * @Method("GET")
     */
    public function showAction(Campaignaward $campaignaward)
    {
        $entity = 'Campaignaward';
        $deleteForm = $this->createDeleteForm($campaignaward);

        return $this->render(strtolower($entity).'/show.html.twig', array(
            'campaignaward' => $campaignaward,
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing Campaignaward entity.
     *
     * @Route("/edit/{id}", name="campaignaward_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Campaignaward $campaignaward)
    {
        $entity = 'Campaignaward';
        $deleteForm = $this->createDeleteForm($campaignaward);
        $editForm = $this->createForm('AppBundle\Form\CampaignawardType', $campaignaward);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($campaignaward);
            $em->flush();

            return $this->redirectToRoute('campaignaward_edit', array('id' => $campaignaward->getId()));
        }

        return $this->render('crud/edit.html.twig', array(
            'campaignaward' => $campaignaward,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Deletes a Campaignaward entity.
     *
     * @Route("/delete/{id}", name="campaignaward_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Campaignaward $campaignaward)
    {
        $entity = 'Campaignaward';
        $form = $this->createDeleteForm($campaignaward);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($campaignaward);
            $em->flush();
        }

        return $this->redirectToRoute('campaignaward_index');
    }

    /**
     * Creates a form to delete a Campaignaward entity.
     *
     * @param Campaignaward $campaignaward The Campaignaward entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Campaignaward $campaignaward)
    {
        $entity = 'Campaignaward';

        return $this->createFormBuilder()
            ->setAction($this->generateUrl('campaignaward_delete', array('id' => $campaignaward->getId())))
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
