<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\User;
use AppBundle\Entity\Invitation;
/**
 * Invitation controller.
 *
 * @Route("/manage/invitation")
 */
class InvitationController extends Controller
{
    /**
     * Lists all User entities.
     *
     * @Route("/", name="invitation_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $entity = 'Invitation';
        $em = $this->getDoctrine()->getManager();

        $invitations = $em->getRepository('AppBundle:Invitation')->findAll();

        return $this->render(strtolower($entity).'/index.html.twig', array(
            'invitations' => $invitations,
            'entity' => $entity
        ));
    }

    /**
     * Creates a new Invitation entity.
     *
     * @Route("/new", name="invitation_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $logger = $this->get('logger');
        $entity = 'Invitation';
        $invitation = new Invitation();
        $form = $this->createForm('AppBundle\Form\InvitationType', $invitation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $invitation->send();
            $logger->debug("Created new Invitation code [".$invitation->getCode()."] For Email: ".$invitation->getEmail());
            $this->addFlash(
              'success',
              'Created new Invitation code ['.$invitation->getCode().'] For Email: '.$invitation->getEmail()
            );
            $em->persist($invitation);
            $em->flush();

            // and then just output your $invitation->getCode() to user
            // also don't forget to check invitation as sent: $invitation->send()

            return $this->redirectToRoute('invitation_index');
        }

        return $this->render('crud/new.html.twig', array(
            'invitation' => $invitation,
            'form' => $form->createView(),
            'entity' => $entity
        ));
    }

    /**
     * Finds and displays a Invitation entity.
     *
     * @Route("/{id}", name="invitation_show")
     * @Method("GET")
     */
    public function showAction(Invitation $invitation)
    {
        $entity = 'Invitation';
        $deleteForm = $this->createDeleteForm($invitation);

        return $this->render(strtolower($entity).'/show.html.twig', array(
            'invitation' => $invitation,
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity
        ));
    }


    /**
     * Deletes a Invitation entity.
     *
     * @Route("/{id}", name="invitation_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Invitation $invitation)
    {
        $entity = 'Invitation';
        $form = $this->createDeleteForm($invitation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($invitation);
            $em->flush();
        }

        return $this->redirectToRoute('invitation_index');
    }

    /**
     * Creates a form to delete a User entity.
     *
     * @param User $user The User entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Invitation $invitation)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('invitation_delete', array('id' => $invitation->getCode())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

}
