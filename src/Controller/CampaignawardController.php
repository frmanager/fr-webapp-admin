<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Campaignaward;
use App\Entity\Campaign;
use App\Entity\Campaignawardtype;
use App\Entity\Campaignawardstyle;
use App\Utils\CampaignHelper;
use DateTime;

/**
 * Campaignaward controller.
 *
 * @Route("/{campaignUrl}/awards")
 */
class CampaignawardController extends Controller
{
    /**
     * Lists all Campaignaward entities.
     *
     * @Route("/", name="campaignaward_index")
     * @Method("GET")
     */
    public function indexAction($campaignUrl, LoggerInterface $logger)
    {
        $entity = 'Campaignaward';
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

        $campaignawardtypes = $em->getRepository('App:Campaignawardtype')->findAll();
        $campaignawardstyles = $em->getRepository('App:Campaignawardstyle')->findAll();
        $campaignawards = $em->getRepository('App:'.$entity)->findByCampaign($campaign);

        return $this->render('campaignAward/campaignAward.index.html.twig', array(
            'campaignawards' => $campaignawards,
            'entity' => $entity,
            'campaign' => $campaign,
        ));
    }

    /**
     * Creates a new Campaignaward entity.
     *
     * @Route("/new", name="campaignaward_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, $campaignUrl, LoggerInterface $logger)
    {
        
        $em = $this->getDoctrine()->getManager();
        $entity = 'Campaignaward';

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

        $campaignAward = new Campaignaward();

        if ($request->isMethod('POST')) {
            $params = $request->request->all();
            $fail = false;

            if(!$fail && empty($params['campaignAward']['name'])){
              $this->addFlash('warning','Award name is required');
              $fail = true;
            }

            if(!$fail && empty($params['campaignAward']['campaignAwardTypeId'])){
              $this->addFlash('warning','Award Type is required');
              $fail = true;
            }else{
              $campaignAwardType = $em->getRepository('App:Campaignawardtype')->find($params['campaignAward']['campaignAwardTypeId']);
              if(is_null($campaignAwardType)){
                $this->addFlash('warning','No Valid Award Type was selected');
                $fail = true;
              }
            }

            if(!$fail && empty($params['campaignAward']['campaignAwardStyleId'])){
              $this->addFlash('warning','Award Style is required');
              $fail = true;
            }else{
              $campaignAwardStyle = $em->getRepository('App:Campaignawardstyle')->find($params['campaignAward']['campaignAwardStyleId']);
              if(is_null($campaignAwardStyle)){
                $this->addFlash('warning','No Valid Award Style was selected');
                $fail = true;
              }
            }

            if (!$fail && $campaignAwardStyle->getValue() == 'level') {
                if (empty($params['campaignAward']['amount'])) {
                    $fail = true;
                    $this->addFlash(
                    'danger',
                    'If '.$campaignAwardStyle->getDisplayName().' is selected, you must have an associated amount'
                );
                } else {
                    if ($params['campaignAward']['amount'] < 0.01) {
                        $fail = true;
                        $this->addFlash(
                      'danger',
                      'Amount must be greater than $0.01'
                  );
                    }
                    $params['campaignAward']['place'] = null;
                }
            }

            if (!$fail && $campaignAwardStyle->getValue() == 'place') {
                if (empty($params['campaignAward']['place'])) {
                    $fail = true;
                    $this->addFlash(
                    'danger',
                    'If '.$campaignAwardStyle->getDisplayName().' is selected, you must have an associated place'
                );
                } else {
                    if ($params['campaignAward']['place'] < 1) {
                        $fail = true;
                        $this->addFlash(
                      'danger',
                      'Place must be greater than 0'
                  );
                    }
                    $params['campaignAward']['amount'] = null;
                }
            }

            if (!$fail) {
                $campaignAwardCheck = $this->getDoctrine()->getRepository('App:CampaignAward')->findOneBy(
          array('campaign' => $campaign, 'campaignawardtype' => $campaignAwardType, 'campaignawardstyle' => $campaignAwardStyle, 'amount' => $params['campaignAward']['amount'], 'place' => $params['campaignAward']['place'])
          );
                if (!empty($campaignAwardCheck) && $campaignAwardCheck->getId() !== $campaignAward->getId()) {
                    $fail = true;
                    $this->addFlash(
                            'danger',
                            'This combination for an award [Type/Style/Place/Amount] already exists'
                        );
                }
            }

            if(!$fail){

              $campaignAward->setName($params['campaignAward']['name']);
              $campaignAward->setCampaign($campaign);
              $campaignAward->setCampaignAwardStyle($campaignAwardStyle);
              $campaignAward->setCampaignAwardType($campaignAwardType);
              $campaignAward->setAmount($params['campaignAward']['amount']);
              $campaignAward->setPlace($params['campaignAward']['place']);

              if(!empty($params['campaignAward']['description'])){
                $campaignAward->setDescription($params['campaignAward']['description']);
              }

              $em->persist($campaignAward);
              $em->flush();
              return $this->redirectToRoute('campaignaward_index', array('campaignUrl'=> $campaignUrl));

            }

        }

        return $this->render('campaignAward/campaignAward.form.html.twig', array(
            'campaignaward' => $campaignAward,
            'campaignAwardStyles' => $em->getRepository('App:Campaignawardstyle')->findAll(),
            'campaignAwardTypes' => $em->getRepository('App:Campaignawardtype')->findAll(),
            'campaign' => $campaign,
        ));
    }

    /**
     * Finds and displays a Campaignaward entity.
     *
     * @Route("/{campaignAwardID}", name="campaignaward_show")
     * @Method("GET")
     */
    public function showAction($campaignUrl, $campaignAwardID, LoggerInterface $logger)
    {
        
        $em = $this->getDoctrine()->getManager();
        $entity = 'Campaignaward';

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

        //CODE TO CHECK TO SEE IF CAMpAIGNAWARD EXISTS
        $campaignAward = $em->getRepository('App:campaignaward')->find($campaignAwardID);
        if(is_null($campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this award');
          return $this->redirectToRoute('homepage');
        }


        $deleteForm = $this->createDeleteForm($campaignAward, $campaignUrl);

        return $this->render('campaignAward/campaignAward.show.html.twig', array(
            'campaignaward' => $campaignAward,
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
            'campaign' => $em->getRepository('App:Campaign')->findOneByUrl($campaignUrl)
        ));
    }

    /**
     * Displays a form to edit an existing Campaignaward entity.
     *
     * @Route("/edit/{campaignAwardID}", name="campaignaward_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, $campaignUrl, $campaignAwardID, LoggerInterface $logger)
    {
        
        $entity = 'Campaignaward';
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


        //CODE TO CHECK TO SEE IF CAMpAIGNAWARD EXISTS
        $campaignAward = $em->getRepository('App:campaignaward')->find($campaignAwardID);
        if(is_null($campaign)){
          $this->get('session')->getFlashBag()->add('warning', 'We are sorry, we could not find this award');
          return $this->redirectToRoute('homepage');
        }


        if ($request->isMethod('POST')) {
            $params = $request->request->all();
            $fail = false;

            if(!$fail && empty($params['campaignAward']['name'])){
              $this->addFlash('warning','Award name is required');
              $fail = true;
            }

            if(!$fail && empty($params['campaignAward']['campaignAwardTypeId'])){
              $this->addFlash('warning','Award Type is required');
              $fail = true;
            }else{
              $campaignAwardType = $em->getRepository('App:Campaignawardtype')->find($params['campaignAward']['campaignAwardTypeId']);
              if(is_null($campaignAwardType)){
                $this->addFlash('warning','No Valid Award Type was selected');
                $fail = true;
              }
            }

            if(!$fail && empty($params['campaignAward']['campaignAwardStyleId'])){
              $this->addFlash('warning','Award Style is required');
              $fail = true;
            }else{
              $campaignAwardStyle = $em->getRepository('App:Campaignawardstyle')->find($params['campaignAward']['campaignAwardStyleId']);
              if(is_null($campaignAwardStyle)){
                $this->addFlash('warning','No Valid Award Style was selected');
                $fail = true;
              }
            }

            if (!$fail && $campaignAwardStyle->getValue() == 'level') {
                if (empty($params['campaignAward']['amount'])) {
                    $fail = true;
                    $this->addFlash(
                    'danger',
                    'If '.$campaignAwardStyle->getDisplayName().' is selected, you must have an associated amount'
                );
                } else {
                    if ($params['campaignAward']['amount'] < 0.01) {
                        $fail = true;
                        $this->addFlash(
                      'danger',
                      'Amount must be greater than $0.01'
                  );
                    }
                    $params['campaignAward']['place'] = null;
                }
            }

            if (!$fail && $campaignAwardStyle->getValue() == 'place') {
                if (empty($params['campaignAward']['place'])) {
                    $fail = true;
                    $this->addFlash(
                    'danger',
                    'If '.$campaignAwardStyle->getDisplayName().' is selected, you must have an associated place'
                );
                } else {
                    if ($params['campaignAward']['place'] < 1) {
                        $fail = true;
                        $this->addFlash(
                      'danger',
                      'Place must be greater than 0'
                  );
                    }
                    $params['campaignAward']['amount'] = null;
                }
            }

            if (!$fail) {
                $campaignAwardCheck = $this->getDoctrine()->getRepository('App:CampaignAward')->findOneBy(
          array('campaignawardtype' => $campaignAwardType, 'campaignawardstyle' => $campaignAwardStyle, 'amount' => $params['campaignAward']['amount'], 'place' => $params['campaignAward']['place'])
          );
                if (!empty($campaignAwardCheck) && $campaignAwardCheck->getId() !== $campaignAward->getId()) {
                    $fail = true;
                    $this->addFlash(
                            'danger',
                            'This combination for an award [Type/Style/Place/Amount] already exists'
                        );
                }
            }

            if(!$fail){

              $campaignAward->setName($params['campaignAward']['name']);
              $campaignAward->setCampaign($campaign);
              $campaignAward->setCampaignAwardStyle($campaignAwardStyle);
              $campaignAward->setCampaignAwardType($campaignAwardType);
              $campaignAward->setAmount($params['campaignAward']['amount']);
              $campaignAward->setPlace($params['campaignAward']['place']);

              if(!empty($params['campaignAward']['description'])){
                $campaignAward->setDescription($params['campaignAward']['description']);
              }

              $em->persist($campaignAward);
              $em->flush();
              return $this->redirectToRoute('campaignaward_index', array('campaignUrl'=> $campaignUrl));

            }

        }

        return $this->render('campaignAward/campaignAward.form.html.twig', array(
            'campaignAward' => $campaignAward,
            'campaignAwardStyles' => $em->getRepository('App:Campaignawardstyle')->findAll(),
            'campaignAwardTypes' => $em->getRepository('App:Campaignawardtype')->findAll(),
            'campaign' => $campaign,
        ));
    }

    /**
     * Deletes a Campaignaward entity.
     *
     * @Route("/delete/{id}", name="campaignaward_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Campaignaward $campaignaward, $campaignUrl, LoggerInterface $logger)
    {
        
        $em = $this->getDoctrine()->getManager();
        $entity = 'Campaignaward';

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

        $form = $this->createDeleteForm($campaignaward, $campaignUrl);
        $form->handleRequest($request);
        $em = $this->getDoctrine()->getManager();
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
    private function createDeleteForm(Campaignaward $campaignaward, $campaignUrl)
    {
        $entity = 'Campaignaward';

        return $this->createFormBuilder()
            ->setAction($this->generateUrl('campaignaward_delete', array('campaignUrl'=> $campaignUrl, 'id' => $campaignaward->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

}
