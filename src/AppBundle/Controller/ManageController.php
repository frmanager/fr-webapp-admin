<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Utils\CampaignHelper;
use DateTime;

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
        $em = $this->getDoctrine()->getManager();
        $campaignSettings = new CampaignHelper($this->getDoctrine()->getRepository('AppBundle:Campaignsetting')->findAll());

        $campaignawardtype = $em->getRepository('AppBundle:Campaignawardtype')->findOneByValue('teacher');
        $campaignawardstyle = $em->getRepository('AppBundle:Campaignawardstyle')->findOneByValue('level');
        //$teacherCampaignawards = $em->getRepository('AppBundle:Campaignawardtype')->findAllBy(array('campaignawardstyle' => $campaignawardstyle->getId(), 'campaignawardtype' => $campaignawardtype));

        // replace this example code with whatever you need
        return $this->render('manage/index.html.twig', array(
          'campaign_settings' => $campaignSettings->getCampaignSettings(),
          'new_teacher_awards' => $this->getNewTeacherAwards(),
          'teacher_rankings' => $this->getTeacherRanks(10),
          'student_rankings' => $this->getStudentRanks(10),
          'total_donation_amount' => $this->getTotalDonationAmount(),
          'total_number_of_donations' => $this->getTotalNumberOfDonations()
        ));
    }

    public function getStudentRanks($limit)
    {
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();
      /*
      * STUDENT DATA
      */
      $query = $em->createQuery('SELECT s.id as student_id,
                                        s.name as student_name,
                                        t.id as teacher_id,
                                        t.teacherName as teacher_name,
                                        g.id as grade_id,
                                        g.name as grade_name,
                                        sum(d.amount) as donation_amount,
                                        count(d.amount) as total_donations
                                   FROM AppBundle:Student s
                        LEFT OUTER JOIN AppBundle:Teacher t
                                   WITH t.id = s.teacher
                        LEFT OUTER JOIN AppBundle:Donation d
                                   WITH s.id = d.student
                        LEFT OUTER JOIN AppBundle:Grade g
                                   WITH g.id = t.grade
                               GROUP BY s.id
                               ORDER BY donation_amount DESC');

            $students = $query->getResult();

      //sorting by amount for rank....
      $query = $em->createQuery('SELECT s.id as student_id,
                                        sum(d.amount) as donation_amount,
                                        count(d.amount) as total_donations
                                   FROM AppBundle:Student s
                        LEFT OUTER JOIN AppBundle:Donation d
                                   WITH s.id = d.student
                               GROUP BY s.id
                               ORDER BY donation_amount DESC');

        $studentSorts = $query->getResult();

        $studentRank = 0;
        $amount = 9999999999999999999; //some astronomical number
        foreach ($studentSorts as $studentSort) {
            //$logger->debug('Current Rank: '.$studentRank.' Current Donation Amount: '.$studentSort['donation_amount'].' amount to beat: '.$amount);
            if ($studentSort['donation_amount'] < $amount) {
                ++$studentRank;
            }

            foreach ($students as &$student) {
                if ($student['student_id'] == $studentSort['student_id']) {
                    $student['rank'] = $studentRank;
                    break;
                }
            }
            $amount = $studentSort['donation_amount'];
        }

        if ($limit > 0) {
            $counter = 1;
            $newArray = [];
            foreach ($students as $student) {
                if ($counter < $limit) {
                    array_push($newArray, $student);
                }
                ++$counter;
            }
            $students = $newArray;
        }

        return $students;

    }

    public function getTeacherRanks($limit)
    {
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();
      /*
      * TEACHER DATA
      */
      $query = $em->createQuery('SELECT t.id as teacher_id,
                                        t.teacherName as teacher_name,
                                        g.id as grade_id,
                                        g.name as grade_name,
                                        sum(d.amount) as donation_amount,
                                        count(d.amount) as total_donations
                                   FROM AppBundle:Teacher t
                        LEFT OUTER JOIN AppBundle:Student s
                                   WITH t.id = s.teacher
                        LEFT OUTER JOIN AppBundle:Donation d
                                   WITH s.id = d.student
                        LEFT OUTER JOIN AppBundle:Grade g
                                   WITH g.id = t.grade
                               GROUP BY t.id
                               ORDER BY donation_amount DESC');

        $teachers = $query->getResult();

      //sorting by amount for rank....
      $query = $em->createQuery('SELECT t.id as teacher_id,
                                        sum(d.amount) as donation_amount,
                                        count(d.amount) as total_donations
                                   FROM AppBundle:Teacher t
                        LEFT OUTER JOIN AppBundle:Student s
                                   WITH t.id = s.teacher
                        LEFT OUTER JOIN AppBundle:Donation d
                                   WITH s.id = d.student
                               GROUP BY t.id
                               ORDER BY donation_amount DESC');

        $teachersorts = $query->getResult();

        $teacherRank = 0;
        $amount = 9999999999999999999; //some astronomical number
      foreach ($teachersorts as $teachersort) {
          //$logger->debug('Current Rank: '.$teacherRank.' Current Donation Amount: '.$teachersort['donation_amount'].' amount to beat: '.$amount);
          if ($teachersort['donation_amount'] < $amount) {
              ++$teacherRank;
          }

          foreach ($teachers as &$teacher) {
              if ($teacher['teacher_id'] == $teachersort['teacher_id']) {
                  $teacher['rank'] = $teacherRank;
                  break;
              }
          }
          $amount = $teachersort['donation_amount'];
      }

        if ($limit > 0) {
            $counter = 1;
            $newArray = [];
            foreach ($teachers as $teacher) {
                            $logger->debug(print_r($teacher, true));
                if ($counter < $limit) {
                    array_push($newArray, $teacher);
                }
                ++$counter;
            }
            $teachers = $newArray;

        }

        return $teachers;
    }



        /**
         * Gets teacher ID and donation amounts by Dat
         * returns object.
         */
        public function getNewTeacherAwards()
        {
            /* WORKING QUERY
                      SELECT t.id as teacher_id,
                            DATE(d.donated_at) as donated_at,
                            sum(d.amount) as donation_amount
                       FROM teacher t
                       JOIN student s
                         ON t.id = s.teacher_id
                       JOIN donation d
                         ON s.id = d.student_id
                   GROUP BY DATE(d.donated_at), t.id
                   ORDER BY DATE(d.donated_at) DESC
              */

              $logger = $this->get('logger');
            $em = $this->getDoctrine()->getManager();
              /*
              * TEACHER DATA
              */
              $query = $em->createQuery('SELECT t.id as teacher_id,
                                                t.teacherName as teacher_name,
                                                g.id as grade_id,
                                                g.name as grade_name,
                                                d.donatedAt as donated_at,
                                                sum(d.amount) as donation_amount,
                                                count(d.amount) as total_donations
                                           FROM AppBundle:Teacher t
                                           JOIN AppBundle:Student s
                                           WITH t.id = s.teacher
                                           JOIN AppBundle:Donation d
                                           WITH s.id = d.student
                                           JOIN AppBundle:Grade g
                                           WITH g.id = t.grade
                                       GROUP BY d.donatedAt, t.id
                                       ORDER BY t.id ASC, d.donatedAt ASC');

            $teacherDonationAmountsByDay = $query->getResult();
            $campaignawardtype = $em->getRepository('AppBundle:Campaignawardtype')->findOneByValue('teacher');
            $campaignawardstyle = $em->getRepository('AppBundle:Campaignawardstyle')->findOneByValue('level');

              //GETTING AWARD DATA
              $qb = $em->createQueryBuilder()->select('u')
                   ->from('AppBundle:Campaignaward', 'u')
                   ->andWhere('u.campaignawardtype = :awardType')
                   ->andWhere('u.campaignawardstyle = :awardStyle')
                   ->setParameter('awardStyle', $campaignawardstyle->getId())
                   ->setParameter('awardType', $campaignawardtype->getId())
                   ->orderBy('u.amount', 'ASC');

            $teacherCampaignawards = $qb->getQuery()->getResult();

              //ADDING AWARD DATA TO $teacherDonationAmountsByDay. WE WILL COMPARE THIS AGAINST TODAYS TOTALS
              foreach ($teacherDonationAmountsByDay as &$teacher) {
                  $sumAmount = 0;
                //GETTING CUMULATIVE SUM FOR EACH DAY
                foreach ($teacherDonationAmountsByDay as $thisRecord) {
                    if ($teacher['teacher_id'] == $thisRecord['teacher_id'] && $teacher['donated_at'] >= $thisRecord['donated_at']) {
                        $sumAmount += $thisRecord['donation_amount'];
                    }
                }

                  foreach ($teacherCampaignawards as $teacherCampaignaward) {
                      if ($teacherCampaignaward->getAmount() <= $sumAmount) {
                          $teacher['campaignaward_id'] = $teacherCampaignaward->getId();
                          $teacher['campaignaward_name'] = $teacherCampaignaward->getName();
                          $teacher['campaignaward_amount'] = $teacherCampaignaward->getAmount();
                      }
                  }
                  $teacher['cumulative_donation_amount'] = $sumAmount;
                  $logger->debug(print_r($teacher, true));
              }

              //NOW Separate todays awards with the last award
              $tempDate = new DateTime();
            $todaysDate = new DateTime($tempDate->format('Y-m-d'));
            $todaysTeachersWithAwards = [];
            $yesterdaysTeachersWithAwards = [];
            foreach ($teacherDonationAmountsByDay as $outerLoop) {
                if (isset($outerLoop['campaignaward_id'])) {
                    if ($todaysDate == $outerLoop['donated_at']) { //Today
                      $logger->debug(print_r($teacher, true));
                        array_push($todaysTeachersWithAwards, $outerLoop);
                    } else { //Before Today
                    $existsFlag = false;
                        foreach ($yesterdaysTeachersWithAwards as $key => $innerLoop) {
                            if ($innerLoop['teacher_id'] == $outerLoop['teacher_id']) {
                                $existsFlag = true;
                                if ($outerLoop['donated_at'] >= $innerLoop['donated_at']) {
                                    unset($yesterdaysTeachersWithAwards[$key]);
                                    array_push($yesterdaysTeachersWithAwards, $outerLoop);
                                }
                            }
                        }
                        if (!$existsFlag) {
                            array_push($yesterdaysTeachersWithAwards, $outerLoop);
                        }
                    }
                }
            }

              //NOW TAKE THAT NEW LIST

              foreach ($todaysTeachersWithAwards as $key => $outerLoop) {
                  foreach ($yesterdaysTeachersWithAwards as $innerLoop) {
                      if ($outerLoop['campaignaward_amount'] <= $innerLoop['campaignaward_amount']) {
                          unset($todaysTeachersWithAwards[$key]);
                      }
                  }
              }

            $logger->debug('Classes with Awards before today!');
            $logger->debug(print_r($yesterdaysTeachersWithAwards, true));

            $logger->debug('Todays Classes with New Awards!');
            $logger->debug(print_r($todaysTeachersWithAwards, true));

            return $todaysTeachersWithAwards;
        }


    public function getTotalDonationAmount()
    {
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select(array('SUM(u.amount) as total'))->from('AppBundle:Donation', 'u');
        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result[0]['total'];
    }

    public function getTotalNumberOfDonations()
    {
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select(array('COUNT(u.amount) as total'))->from('AppBundle:Donation', 'u');
        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result[0]['total'];
    }
}
