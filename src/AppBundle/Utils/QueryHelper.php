<?php

// src/AppBundle/Utils/QueryHelper.php

namespace AppBundle\Utils;

use Doctrine\ORM\EntityManager;
use DateTime;
use Monolog\Logger;

class QueryHelper
{
    protected $em;
    protected $logger;

    public function __construct(EntityManager $em, Logger $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function getStudentsData(array $options)
    {
        return $this->getData($this->getStudentsDataQuery($options));
    }

    public function getTeachersData(array $options)
    {
        return $this->getData($this->getTeachersDataQuery($options));
    }

    public function getData($queryString)
    {
        //$em = $this->em->getManager();
        return $this->em->createQuery($queryString)->getResult();
    }

    public function getTeacherDonationsByDay(array $options)
    {
        return $this->getData($this->getTeacherDonationsByDayQuery($options));
    }


    public function getTotalDonations(array $options)
    {
        $data = $this->getData($this->getTotalDonationsQuery($options));
        return $data[0];
    }


    public function getStudentsDataQuery(array $options)
    {
        if (isset($options['day_modifier'])) {
            $dayModifier = $options['day_modifier'];
        } else {
            $dayModifier = false;
        }
                //NOW Separate todays awards with the last award...We also kick out any future awards
              $date = new DateTime();
        $dateString = $date->format('Y-m-d').' 00:00:00';
        $todaysDate = DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
        $this->logger->debug('Date Before: '.$todaysDate->format('Y-m-d H:i:s'));

        if ($dayModifier !== false) {
            $todaysDate->modify($dayModifier);
            $this->logger->debug('Date After: '.$todaysDate->format('Y-m-d H:i:s'));
        }

        $queryString = sprintf("SELECT s.id as id,
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
                WHERE d.donatedAt <= '%s'
             GROUP BY s.id
             ORDER BY donation_amount DESC", $todaysDate->format('Y-m-d H:i:s'));

        $this->logger->debug('Query : '.$queryString);

        return $queryString;
    }

    public function getTotalDonationsQuery(array $options)
    {
        if (isset($options['day_modifier'])) {
            $dayModifier = $options['day_modifier'];
        } else {
            $dayModifier = false;
        }
        //NOW Separate todays awards with the last award...We also kick out any future awards
        $date = new DateTime();
        $dateString = $date->format('Y-m-d').' 00:00:00';
        $todaysDate = DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
        $this->logger->debug('Date Before: '.$todaysDate->format('Y-m-d H:i:s'));

        if ($dayModifier !== false) {
            $todaysDate->modify($dayModifier);
            $this->logger->debug('Date After: '.$todaysDate->format('Y-m-d H:i:s'));
        }

        $queryString = sprintf("SELECT sum(d.amount) as donation_amount,
                                       count(d.amount) as total_donations
                                  FROM AppBundle:Donation d
                                 WHERE d.donatedAt <= '%s'", $todaysDate->format('Y-m-d H:i:s'));

        $this->logger->debug('Query : '.$queryString);

        return $queryString;
    }


    public function getTeachersDataQuery(array $options)
    {
        if (isset($options['day_modifier'])) {
            $dayModifier = $options['day_modifier'];
        } else {
            $dayModifier = false;
        }
          //NOW Separate todays awards with the last award...We also kick out any future awards
        $date = new DateTime();
        $dateString = $date->format('Y-m-d').' 00:00:00';
        $todaysDate = DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
        $this->logger->debug('Date Before: '.$todaysDate->format('Y-m-d H:i:s'));

        if ($dayModifier !== false) {
            $todaysDate->modify($dayModifier);
            $this->logger->debug('Date After: '.$todaysDate->format('Y-m-d H:i:s'));
        }

        $queryString = sprintf("SELECT t.id as id,
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
                 WHERE d.donatedAt <= '%s'
              GROUP BY t.id
              ORDER BY donation_amount DESC", $todaysDate->format('Y-m-d H:i:s'));

        $this->logger->debug('Query : '.$queryString);

        return $queryString;
    }

    public function getTeacherDonationsByDayQuery(array $options)
    {

      if (isset($options['day_modifier'])) {
          $dayModifier = $options['day_modifier'];
      } else {
          $dayModifier = false;
      }
        //NOW Separate todays awards with the last award...We also kick out any future awards
      $date = new DateTime();
      $dateString = $date->format('Y-m-d').' 00:00:00';
      $todaysDate = DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
      $this->logger->debug('Date Before: '.$todaysDate->format('Y-m-d H:i:s'));

      if ($dayModifier !== false) {
          $todaysDate->modify($dayModifier);
          $this->logger->debug('Date After: '.$todaysDate->format('Y-m-d H:i:s'));
      }

        $queryString = sprintf("SELECT t.id as id,
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
                  WHERE d.donatedAt <= '%s'
               GROUP BY d.donatedAt, t.id
               ORDER BY t.id ASC, d.donatedAt ASC", $todaysDate->format('Y-m-d H:i:s'));

         $this->logger->debug('Query : '.$queryString);

         return $queryString;
    }

    public function sortObjectbyAmount(array $objects, array $settings)
    {
        if (isset($settings['amount_field'])) {
            $amountField = $settings['amount_field'];
        } else {
            $amountField = 'donation_amount';
        }

        $listOfAmounts = [];
        foreach ($objects as $key => $value) {
            $listOfAmounts[$key] = $value[$amountField];
        }
        arsort($listOfAmounts);

        $newObjectArray = [];
        foreach ($listOfAmounts as $key => $value) {
            array_push($newObjectArray, $objects[$key]);
        }

        return $objects;
    }

    public function getRanks(array $objects, array $settings)
    {
        if (isset($settings['amount_field'])) {
            $amountField = $settings['amount_field'];
        } else {
            $amountField = 'donation_amount';
        }

        if (isset($settings['limit'])) {
            $limit = $settings['limit'];
        } else {
            $limit = 0;
        }

        $sortedObjects = $this->sortObjectbyAmount($objects, $settings);

        $rank = 0;
        $amount = 9999999999999999999; //some astronomical number
        foreach ($sortedObjects as $sortedObject) {
            if ($sortedObject[$amountField] < $amount) {
                ++$rank;
            }

            foreach ($objects as &$object) {
                if ($object['id'] == $sortedObject['id']) {
                    $object['rank'] = $rank;
                    break;
                }
            }
            $amount = $sortedObject[$amountField];
        }

        if ($limit > 0) {
            $counter = 1;
            $newArray = [];
            foreach ($objects as $object) {
                if ($counter < $limit) {
                    array_push($newArray, $object);
                }
                ++$counter;
            }
            $objects = $newArray;
        }

        return $objects;
    }


    public function getCampaignAwards($type, $style)
    {
        $campaignawardtype = $this->em->getRepository('AppBundle:Campaignawardtype')->findOneByValue('teacher');
        $campaignawardstyle = $this->em->getRepository('AppBundle:Campaignawardstyle')->findOneByValue('level');
        $qb = $this->em->createQueryBuilder()->select('u')
             ->from('AppBundle:Campaignaward', 'u')
             ->andWhere('u.campaignawardtype = :awardType')
             ->andWhere('u.campaignawardstyle = :awardStyle')
             ->setParameter('awardStyle', $campaignawardstyle->getId())
             ->setParameter('awardType', $campaignawardtype->getId())
             ->orderBy('u.amount', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function getTeacherRanks(array $options)
    {
        return $this->getRanks($this->getTeachersData($options), $options);
    }

    public function getStudentRanks(array $options)
    {
        return $this->getRanks($this->getStudentsData($options), $options);
    }

    /**
     * Gets teacher ID and donation amounts by Dat
     * returns object.
     */
    public function getNewTeacherAwards(array $options)
    {
        if (isset($options['day_modifier'])) {
            $dayModifier = $options['day_modifier'];
        } else {
            $dayModifier = false;
        }

        $teacherDonationAmountsByDay = $this->getTeacherDonationsByDay($options);
        $teacherCampaignawards = $this->getCampaignAwards('teacher', 'level');

          //ADDING AWARD DATA TO $teacherDonationAmountsByDay. WE WILL COMPARE THIS AGAINST TODAYS TOTALS
          foreach ($teacherDonationAmountsByDay as &$teacher) {
              $sumAmount = 0;
            //GETTING CUMULATIVE SUM FOR EACH DAY
            foreach ($teacherDonationAmountsByDay as $thisRecord) {
                if ($teacher['id'] == $thisRecord['id'] && $teacher['donated_at'] >= $thisRecord['donated_at']) {
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
              $this->logger->debug(print_r($teacher, true));
          }

          //NOW Separate todays awards with the last award...We also kick out any future awards
        $date = new DateTime();
        $dateString = $date->format('Y-m-d').' 00:00:00';
        $todaysDate = DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
        $this->logger->debug('Date Before: '.print_r($todaysDate, true));

        if ($dayModifier !== false) {
            $todaysDate->modify($dayModifier);
        }

        $todaysTeachersWithAwards = [];
        $yesterdaysTeachersWithAwards = [];
        foreach ($teacherDonationAmountsByDay as $outerLoop) {
            if (isset($outerLoop['campaignaward_id'])) {
                if ($todaysDate == $outerLoop['donated_at']) { //Today
                  $this->logger->debug(print_r($teacher, true));
                    array_push($todaysTeachersWithAwards, $outerLoop);
                } elseif ($todaysDate > $outerLoop['donated_at']) { //Today
                $existsFlag = false;
                    foreach ($yesterdaysTeachersWithAwards as $key => $innerLoop) {
                        if ($innerLoop['id'] == $outerLoop['id']) {
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
                  if ($outerLoop['id'] == $innerLoop['id'] && $outerLoop['campaignaward_amount'] <= $innerLoop['campaignaward_amount']) {
                      unset($todaysTeachersWithAwards[$key]);
                  }
              }
          }

        $this->logger->debug('Classes with Awards before today!');
        $this->logger->debug(print_r($yesterdaysTeachersWithAwards, true));

        $this->logger->debug('Todays Classes with New Awards!');
        $this->logger->debug(print_r($todaysTeachersWithAwards, true));

        return $todaysTeachersWithAwards;
    }
}
