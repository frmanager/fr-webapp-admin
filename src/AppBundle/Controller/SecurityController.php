<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Security Controller
 *
 */
class SecurityController extends Controller
{


  /**
   * @Route("/login", name="login")
   */
  public function loginAction(Request $request)
  {
      $authUtils = $this->get('security.authentication_utils');

      $logger = $this->get('logger');
      /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
      $session = $request->getSession();

      // get the login error if there is one
      $error = $authUtils->getLastAuthenticationError();

      // last username entered by the user
      $lastUsername = $authUtils->getLastUsername();

      return $this->render('security/login.html.twig', array(
          'last_username' => $lastUsername,
          'error'         => $error,
      ));
  }

  /**
   * @Route("/loginRedirect", name="loginRedirect")
   */
  public function loginRedirectAction(Request $request)
  {
      $logger = $this->get('logger');

      $authUtils = $this->get('security.authentication_utils');
      $session = $request->getSession();

      $logger->debug("Checking to see if user is confirmed");
      $user = $this->get('security.token_storage')->getToken()->getUser();
      //CODE TO CHECK TO SEE IF A USERS TEAM EXISTS, IF NOT, THEY NEED TO CREATE ONE
      if($user->getUserStatus()->getName() !== "Confirmed"){
        $logger->debug("User is not fully registered, sending to confirm_email");
        $this->get('session')->getFlashBag()->add('warning', 'Hi, it looks like you have not confirmed your email yet.');
        return $this->redirectToRoute('confirm_email', array('campaignUrl' => $campaign->getUrl()));
      }

      return $this->redirectToRoute('homepage', array('action' => 'list_campaigns'));
  }


  /**
   * @Route("/logout", name="logout")
   */
  public function logoutAction(Request $request)
  {
      $authUtils = $this->get('security.authentication_utils');

      $logger = $this->get('logger');
      /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
      $session = $request->getSession();

      return $this->redirectToRoute('homepage');
  }


  /**
   * Displays a form to edit an existing User Account.
   *
   * @Route("/profile", name="profile_edit")
   * @Method({"GET", "POST"})
   */
  public function editAction(Request $request)
  {
      $entity = 'User';
      $user = $this->get('security.token_storage')->getToken()->getUser();
      $em = $this->getDoctrine()->getManager();

      //TODO: GET Referrer campaign to redirect after completion....

      $editForm = $this->createForm('AppBundle\Form\UserType', $user);
      $editForm->handleRequest($request);

      if ($editForm->isSubmitted() && $editForm->isValid()) {
          $em = $this->getDoctrine()->getManager();
          $em->persist($grade);
          $em->flush();

          return $this->redirectToRoute('homepage');
      }

      return $this->render('security/profile.html.twig', array(
          'user' => $user,
          'edit_form' => $editForm->createView(),
          'entity' => $entity
      ));
  }


}
