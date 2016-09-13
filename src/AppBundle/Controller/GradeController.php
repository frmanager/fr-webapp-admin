<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Grade;

/**
 * Grade controller.
 *
 * @Route("/admin/grade")
 */
class GradeController extends Controller
{
    /**
     * Lists all Grade entities.
     *
     * @Route("/", name="grade_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $entity = 'Grade';
        $em = $this->getDoctrine()->getManager();

        $grades = $em->getRepository('AppBundle:Grade')->findAll();

        return $this->render(strtolower($entity).'/index.html.twig', array(
            'grades' => $grades,
            'entity' => $entity,
        ));
    }

    /**
     * Creates a new Grade entity.
     *
     * @Route("/new", name="grade_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $entity = 'Grade';
        $grade = new Grade();
        $form = $this->createForm('AppBundle\Form\GradeType', $grade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($grade);
            $em->flush();

            return $this->redirectToRoute('grade_index', array('id' => $grade->getId()));
        }

        return $this->render('crud/new.html.twig', array(
            'grade' => $grade,
            'form' => $form->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Finds and displays a Grade entity.
     *
     * @Route("/show/{id}", name="grade_show")
     * @Method("GET")
     */
    public function showAction(Grade $grade)
    {
        $entity = 'Grade';
        $deleteForm = $this->createDeleteForm($grade);

        return $this->render(strtolower($entity).'/show.html.twig', array(
            'grade' => $grade,
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing Grade entity.
     *
     * @Route("/edit/{id}", name="grade_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Grade $grade)
    {
        $entity = 'Grade';
        $deleteForm = $this->createDeleteForm($grade);
        $editForm = $this->createForm('AppBundle\Form\GradeType', $grade);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($grade);
            $em->flush();

            return $this->redirectToRoute('grade_edit', array('id' => $grade->getId()));
        }

        return $this->render('crud/edit.html.twig', array(
            'grade' => $grade,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
        ));
    }

    /**
     * Deletes a Grade entity.
     *
     * @Route("/delete/{id}", name="grade_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Grade $grade)
    {
        $entity = 'Grade';
        $form = $this->createDeleteForm($grade);
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
    private function createDeleteForm(Grade $grade)
    {
        $entity = 'Grade';

        return $this->createFormBuilder()
            ->setAction($this->generateUrl('grade_delete', array('id' => $grade->getId())))
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
