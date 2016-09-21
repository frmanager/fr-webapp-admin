<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Grade controller.
 *
 * @Route("/manage")
 */
class ManageController extends Controller
{
    /**
     * @Route("/", name="manage_index")
     */
    public function indexAction(Request $request)
    {
        $logger = $this->get('logger');
        $entity = 'Causevoxteam';
        $em = $this->getDoctrine()->getManager();
        $data = [];
        $data['total_donation_amount'] = 0;
        $data['number_of_donations'] = 0;

        $qb = $em->createQueryBuilder();
        $qb->select(array('count(u.amount) as number_of_donations', 'sum(u.amount) as total_donation_amount'))->from('AppBundle:Donation', 'u');
        $query = $qb->getQuery();
        $result = $query->getResult();
        $logger->debug('Total Donations: '.$result[0]['total_donation_amount']);
        $data['total_donation_amount'] += $result[0]['total_donation_amount'];
        $data['number_of_donations'] = +$result[0]['number_of_donations'];

        $campaignsettings = $em->getRepository('AppBundle:Campaignsetting')->findAll();

        foreach ($campaignsettings as $campaignsetting) {
            $name = $campaignsetting->getDisplayName();
            $value = $campaignsetting->getValue();
            if (strcmp($name, 'campaign_start_date') == 0) {
                $data['campaign_start_date'] = date('Y-m-d', strtotime($value));
            } elseif (strcmp($name, 'campaign_end_date') == 0) {
                $data['campaign_end_date'] = date('Y-m-d', strtotime($value));
                $data['campaign_end_date_countdown'] = date('Y/m/d', strtotime($value));
            } elseif (strcmp($name, 'campaign_funding_goal') == 0) {
                $data['campaign_funding_goal'] = (float) $value;
            } elseif (strcmp($name, 'campaign_url') == 0) {
                $data['campaign_url'] = $value;
            }
        }

        $query = $em->createQuery('SELECT t.id as teacher_id,
                                    	    g.name as grade_name,
                                          t.teacherName,
                                    	    sum(o.amount) as donation_amount,
                                          count(o.amount) as total_donations
                                     FROM AppBundle:Teacher t
                                     JOIN AppBundle:Student s
                                     WITH t.id = s.teacher
                                     JOIN AppBundle:Donation o
                                     WITH s.id = o.student
                                     JOIN AppBundle:Grade g
                                     WITH g.id = t.grade
                                    GROUP
                                       BY t.teacherName
                                    ORDER
                                       BY g.name,
                                          t.teacherName');

        $result = $query->getResult();

        $logger->debug(print_r($result, true));

        $data['teachers'] = $result;

        // replace this example code with whatever you need
        return $this->render('manage/index.html.twig', $data);
    }
}
