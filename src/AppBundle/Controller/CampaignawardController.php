<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Campaignaward;
use AppBundle\Entity\Campaignawardtype;
use AppBundle\Entity\Campaignawardstyle;

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

        $campaignawardtypes = $em->getRepository('AppBundle:Campaignawardtype')->findAll();
        if (empty($campaignawardtypes)) {
            $defaultCampaignawardtypes = [];

            array_push($defaultCampaignawardtypes, array('displayName' => 'Teacher/Class', 'value' => 'teacher', 'description' => ''));
            array_push($defaultCampaignawardtypes, array('displayName' => 'Student/Individual', 'value' => 'student', 'description' => ''));

            foreach ($defaultCampaignawardtypes as $defaultCampaignawardtype) {
                $em = $this->getDoctrine()->getManager();

                $campaignawardtype = new Campaignawardtype();
                $campaignawardtype->setDisplayName($defaultCampaignawardtype['displayName']);
                $campaignawardtype->setValue($defaultCampaignawardtype['value']);
                $campaignawardtype->setDescription($defaultCampaignawardtype['description']);

                $em->persist($campaignawardtype);
                $em->flush();
            }
        }

        $campaignawardstyles = $em->getRepository('AppBundle:Campaignawardstyle')->findAll();
        if (empty($campaignawardstyles)) {
            $defaultCampaignawardstyles = [];

            array_push($defaultCampaignawardstyles, array('displayName' => 'Place', 'value' => 'place', 'description' => ''));
            array_push($defaultCampaignawardstyles, array('displayName' => 'Donation Level', 'value' => 'level', 'description' => 'award received if (Teacher/Student) reach donation amount'));

            foreach ($defaultCampaignawardstyles as $defaultCampaignawardstyle) {
                $em = $this->getDoctrine()->getManager();

                $campaignawardstyle = new Campaignawardstyle();
                $campaignawardstyle->setDisplayName($defaultCampaignawardstyle['displayName']);
                $campaignawardstyle->setValue($defaultCampaignawardstyle['value']);
                $campaignawardstyle->setDescription($defaultCampaignawardstyle['description']);

                $em->persist($campaignawardstyle);
                $em->flush();
            }
        }

        $em->clear();

        $campaignawards = $em->getRepository('AppBundle:'.$entity)->findAll();

        return $this->render(strtolower($entity).'/index.html.twig', array(
            'campaignawards' => $campaignawards,
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

}
