<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Classroom;
use AppBundle\Entity\Grade;
use AppBundle\Entity\Campaign;
use AppBundle\Utils\ValidationHelper;
use AppBundle\Utils\CSVHelper;
use AppBundle\Utils\CampaignHelper;
use AppBundle\Utils\QueryHelper;
use DateTime;

/**
 * Classroom controller.
 *
 * @Route("/{campaignUrl}/classrooms")
 */
class ClassroomController extends Controller
{
  /**
   * Lists all Classroom entities.
   *
   * @Route("/", name="classroom_index")
   * @Method({"GET", "POST"})
   */
  public function classroomIndexAction($campaignUrl)
  {
      $logger = $this->get('logger');
      $entity = 'Classroom';
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

      $queryHelper = new QueryHelper($em, $logger);
      $tempDate = new DateTime();
      $dateString = $tempDate->format('Y-m-d').' 00:00:00';
      $reportDate = DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
      // replace this example code with whatever you need
      return $this->render('campaign/classroom.index.html.twig', array(
        'classrooms' => $queryHelper->getClassroomRanks(array('campaign' => $campaign, 'limit'=> 0)),
        'entity' => strtolower($entity),
        'campaign' => $campaign,
      ));

  }

    /**
     * Creates a new Classroom entity.
     *
     * @Route("/new", name="classroom_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, $campaignUrl)
    {
        $entity = 'Classroom';
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

        $classroom = new Classroom();
        $form = $this->createForm('AppBundle\Form\ClassroomType', $classroom);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($classroom);
            $em->flush();

            return $this->redirectToRoute('classroom_index', array('id' => $classroom->getId()));
        }

        return $this->render('crud/new.html.twig', array(
            'classroom' => $classroom,
            'form' => $form->createView(),
            'entity' => $entity,
            'campaign' => $em->getRepository('AppBundle:Campaign')->findOneByUrl($campaignUrl),
        ));
    }

    /**
     * Finds and displays a Classroom entity.
     *
     * @Route("/{id}", name="classroom_show")
     * @Method("GET")
     */
    public function showAction(Classroom $classroom, $campaignUrl)
    {
        $logger = $this->get('logger');
        $entity = 'Classroom';
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

        $deleteForm = $this->createDeleteForm($classroom,$campaignUrl);
        $classroom = $this->getDoctrine()->getRepository('AppBundle:'.strtolower($entity))->findOneById($classroom->getId());
        //$logger->debug(print_r($student->getDonations()));

        $qb = $em->createQueryBuilder()->select('u')
               ->from('AppBundle:Campaignaward', 'u')
               ->andWhere('u.campaign = :campaignId')
               ->setParameter('campaignId', $campaign->getId())
               ->orderBy('u.amount', 'DESC');

        $campaignAwards = $qb->getQuery()->getResult();

        $queryHelper = new QueryHelper($em, $logger);

        return $this->render('campaign/classroom.show.html.twig', array(
            'classroom' => $classroom,
            'classroom_rank' => $queryHelper->getClassroomRank($classroom->getId(),array('campaign' => $campaign, 'limit' => 0)),
            'campaign_awards' => $campaignAwards,
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
            'campaign' => $campaign,
        ));
    }

    /**
     * Displays a form to edit an existing Classroom entity.
     *
     * @Route("/edit/{id}", name="classroom_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Classroom $classroom, $campaignUrl)
    {
        $entity = 'Classroom';
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

        $deleteForm = $this->createDeleteForm($classroom, $campaignUrl);
        $editForm = $this->createForm('AppBundle\Form\ClassroomType', $classroom);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($classroom);
            $em->flush();

            return $this->redirectToRoute('classroom_index', array('campaignUrl' => $campaign->getUrl()));
        }

        return $this->render('crud/edit.html.twig', array(
            'classroom' => $classroom,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'entity' => $entity,
            'campaign' => $campaign
        ));
    }

    /**
     * Deletes a Classroom entity.
     *
     * @Route("/delete/{id}", name="classroom_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Classroom $classroom, $campaignUrl)
    {
        $entity = 'Classroom';
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

        $form = $this->createDeleteForm($classroom);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($classroom);
            $em->flush();
        }

        return $this->redirectToRoute('homepage');
    }

    /**
     * Creates a form to delete a Classroom entity.
     *
     * @param Classroom $classroom The Classroom entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Classroom $classroom, $campaignUrl)
    {
        $entity = 'Classroom';
        $logger = $this->get('logger');
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('campaign_delete', array('campaignUrl'=> $campaignUrl, 'id' => $classroom->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Creates a new Classroom entity.
     *
     * @Route("/upload", name="classroom_upload")
     * @Method({"GET", "POST"})
     */
    public function uploadForm(Request $request)
    {
        $logger = $this->get('logger');
        $entity = 'Classroom';
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


        $mode = 'update';
        $form = $this->createForm('AppBundle\Form\UploadType', array('entity' => $entity, 'file_type' => $entity, 'role' => $this->getUser()->getRoles()));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null != $form['upload_mode']->getData()) {
                $mode = $form['upload_mode']->getData();
            } else {
                $logger->error('No mode was selected. defaulted to update');
            }

            $uploadFile = $form['attachment']->getData();

            if (strpos($uploadFile->getClientOriginalName(), '.csv') !== false) {
                $logger->info('File was a .csv, attempting to load');

                $uploadFile->move('temp/', strtolower($entity).'.csv');

                $CSVHelper = new CSVHelper();
                $CSVHelper->processFile('temp/', strtolower($entity).'.csv');
                $templateFields = array('classrooms_name', 'grade', 'email');

                if ($CSVHelper->validateHeaders($templateFields)) {
                    $logger->info('Making changes to database');

                    $em = $this->getDoctrine()->getManager();

                    if (strcmp($mode, 'truncate') == 0) {
                        $logger->info('User selected to [truncate] table');

                        $qb = $em->createQueryBuilder();
                        $qb->delete('AppBundle:'.$entity, 's');
                        $query = $qb->getQuery();

                        $query->getResult();

                        $em->flush();

                        $this->addFlash(
                            'info',
                            'The Classrooms table has been truncated'
                        );
                    }

                    $logger->info('Uploading Data');
                    $em = $this->getDoctrine()->getManager();
                    $errorMessages = [];
                    $errorMessage;

                    foreach ($CSVHelper->getData() as $i => $item) {
                        $failure = false;
                        unset($errorMessage);
                        $logger->debug('Row ['.$i.'] data: '.print_r($item, true));
                        if (!$failure) {
                            $grade = $this->getDoctrine()->getRepository('AppBundle:Grade')->findOneByName($item['grade']);
                            if (empty($grade)) {
                                $failure = true;
                                $errorMessage = new ValidationHelper(array(
                            'entity' => $entity,
                            'row_index' => ($i + 2),
                            'error_field' => 'grade',
                            'error_field_value' => $item['grade'],
                            'error_message' => 'Could not find grade',
                            'error_level' => ValidationHelper::$level_error, ));
                            }
                        }

                        if (!$failure) {
                            $classroom = $this->getDoctrine()->getRepository('AppBundle:'.$entity)->findOneBy(
                      array('grade' => $grade->getId(), 'classroomName' => $item['classrooms_name'])
                      );
                      //Going to perform "Insert" vs "Update"
                        if (empty($classroom)) {
                            $logger->debug($entity.' not found....creating new record');
                            $classroom = new Classroom();
                        } else {
                            $logger->debug($entity.' found....updating existing record');
                            if (strcmp($mode, 'truncate') == 0) {
                                //This means there is a duplicate in the file...
                            $failure = true;
                                $errorMessage = new ValidationHelper(array(
                              'entity' => $entity,
                              'row_index' => ($i + 2),
                              'error_field' => 'classrooms_name',
                              'error_field_value' => $item['classrooms_name'],
                              'error_message' => 'Duplicate with '.$entity.' #'.$classroom->getId(),
                              'error_level' => ValidationHelper::$level_warning, ));
                            }
                        }
                            if (!$failure) {
                                $classroom->setClassroomName($item['classrooms_name']);
                                $classroom->setGrade($grade);
                                $classroom->setEmail($item['email']);

                                $validator = $this->get('validator');
                                $errors = $validator->validate($classroom);

                                if (strcmp($mode, 'validate') !== 0) {
                                    if (count($errors) > 0) {
                                        $errorsString = (string) $errors;
                                        $logger->error('[ROW #'.($i + 2).'] Could not add ['.$entity.']: '.$errorsString);
                                        $this->addFlash(
                                    'danger',
                                    '[ROW #'.($i + 2).'] Could not add ['.$entity.']: '.$errorsString
                                );
                                    } else {
                                        $em->persist($classroom);
                                        $em->flush();
                                        $em->clear();
                                    }
                                } //Otherwise we do Nothing....
                            }
                        }
                        if (isset($errorMessage) && strcmp($mode, 'validate') !== 0) {
                            $this->addFlash(
                                $errorMessage->getErrorLevel(),
                                $errorMessage->printFlashBagMessage()
                            );
                        }

                      //Push Error Message
                      if (isset($errorMessage)) {
                          array_push($errorMessages, $errorMessage->getMap());
                      }
                    }

                    if (strcmp($mode, 'validate') !== 0) {
                        $em->flush();
                        $em->clear();

                        return $this->redirectToRoute(strtolower($entity).'_index');
                    } else {
                        return $this->render('crud/validate.html.twig', array(
                          'error_messages' => $errorMessages,
                          'entity' => $entity,
                      ));
                    }
                } else {
                    $logger->info('file does not have mandatory fields. ['.implode(', ', $templateFields).']');
                    $logger->info('File was not a .csv');
                    $this->addFlash(
                      'danger',
                      'file does not have mandatory fields. ['.implode(', ', $templateFields).']'
                  );
                }
            } else {
                $logger->info('File was not a .csv');
                $this->addFlash(
                    'danger',
                    'File was not a .csv'
                );
            }
        }

        return $this->render('crud/upload.html.twig', array(
            'form' => $form->createView(),
            'entity' => $entity,
            'file_type' => $entity,
        ));
    }


}
