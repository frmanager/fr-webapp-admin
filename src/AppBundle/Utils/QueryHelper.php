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
        if (isset($options['before_date'])) {
            $date = "AND d.donatedAt <= '".$this->convertToDay($options['before_date'])->format('Y-m-d H:i:s')."' ";
        } else {
            $date = '';
        }

        if (isset($options['id'])) {
            $whereId = 'WHERE s.id = '.$options['id'];
        } else {
            $whereId = '';
        }

        $queryString = sprintf('SELECT s.id as id,
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
                   %s
      LEFT OUTER JOIN AppBundle:Grade g
                 WITH g.id = t.grade
                   %s
             GROUP BY s.id
             ORDER BY donation_amount DESC', $date, $whereId);

        $this->logger->debug('Query : '.$queryString);

        return $queryString;
    }

    public function getTotalDonationsQuery(array $options)
    {
        if (isset($options['before_date'])) {
            $date = "WHERE d.donatedAt <= '".$this->convertToDay($options['before_date'])->format('Y-m-d H:i:s')."' ";
        } else {
            $date = '';
        }

        $queryString = sprintf('SELECT sum(d.amount) as donation_amount,
                                       count(d.amount) as total_donations
                                  FROM AppBundle:Donation d
                                  %s', $date);

        $this->logger->debug('Query : '.$queryString);

        return $queryString;
    }

    public function getTeachersDataQuery(array $options)
    {
        if (isset($options['before_date'])) {
            $date = "AND d.donatedAt <= '".$this->convertToDay($options['before_date'])->format('Y-m-d H:i:s')."' ";
        } else {
            $date = '';
        }

        if (isset($options['id'])) {
            $whereId = 'WHERE t.id = '.$options['id'];
        } else {
            $whereId = '';
        }

        $queryString = sprintf('SELECT t.id as id,
                                       t.teacherName as teacher_name,
                                       g.id as grade_id,
                                       g.name as grade_name,
                                       sum(d.amount) as donation_amount,
                                       count(d.amount) as total_donations
                                  FROM AppBundle:Teacher t
                       LEFT OUTER JOIN AppBundle:Donation d
                                  WITH t.id = d.teacher
                                   %s
                                  JOIN AppBundle:Grade g
                                  WITH g.id = t.grade
                                    %s
                              GROUP BY t.id
                              ORDER BY donation_amount DESC', $date, $whereId);

        $this->logger->debug('Query : '.$queryString);

        return $queryString;
    }

    public function getTeacherDonationsByDayQuery(array $options)
    {
        if (isset($options['before_date'])) {
            $date = "AND d.donatedAt <= '".$this->convertToDay($options['before_date'])->format('Y-m-d H:i:s')."' ";
        } else {
            $date = '';
        }

        $queryString = sprintf('SELECT t.id as id,
                        t.teacherName as teacher_name,
                        g.id as grade_id,
                        g.name as grade_name,
                        d.donatedAt as donated_at,
                        sum(d.amount) as donation_amount,
                        count(d.amount) as total_donations
                   FROM AppBundle:Teacher t
                   JOIN AppBundle:Donation d
                   WITH t.id = d.teacher
                      %s
                   JOIN AppBundle:Grade g
                   WITH g.id = t.grade
               GROUP BY d.donatedAt, t.id
               ORDER BY t.id ASC, d.donatedAt ASC', $date);

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
            $this->logger->debug('Before sortObjectbyAmount: row ['.$key.'] - '.print_r($value, true));
            $listOfAmounts[$key] = $value[$amountField];
        }
        arsort($listOfAmounts);

        $newObjectArray = [];
        foreach ($listOfAmounts as $key => $value) {
            $this->logger->debug('After sortObjectbyAmount: row ['.$key.'] - '.print_r($value, true));
            array_push($newObjectArray, $objects[$key]);
        }

        return $objects;
    }

    public function getRanks(array $objects, array $settings)
    {
        foreach ($objects as $object) {
            $this->logger->debug('getRanks Input Data: '.print_r($object, true));
        }

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

        //Sorting DESC
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
            $counter = 0;
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

    public function getRank(array $objects, $id, array $settings)
    {
        foreach ($objects as $object) {
            $this->logger->debug('getRanks Input Data: '.print_r($object, true));
        }

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

        //Sorting DESC
        $sortedObjects = $this->sortObjectbyAmount($objects, $settings);

        $rank = 0;
        $amount = 9999999999999999999; //some astronomical number
        foreach ($sortedObjects as $sortedObject) {
            if ($sortedObject[$amountField] < $amount) {
                ++$rank;
            }

            foreach ($objects as $object) {
                if ($object['id'] == $sortedObject['id'] && $sortedObject['id'] == $id) {
                    return $rank;
                    break;
                }
            }
            $amount = $sortedObject[$amountField];
        }

        return false;
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

    public function getTeacherRank($id, array $options)
    {
        return $this->getRank($this->getTeachersData($options), $id, $options);
    }

    public function getStudentRanks(array $options)
    {
        return $this->getRanks($this->getStudentsData($options), $options);
    }

    public function getStudentRank($id, array $options)
    {
        return $this->getRank($this->getStudentsData($options), $id, $options);
    }

    /**
     * Gets teacher ID and donation amounts by Date
     * returns object.
     */
    public function getNewTeacherAwards(array $options)
    {
        if (isset($options['before_date'])) {
            $todaysDate = $this->convertToDay($options['before_date']);
        } else {
            $todaysDate = $this->convertToDay(new DateTime());
        }

        $teacherDonationAmountsByDay = $this->getTeacherAwards($options);

        $todaysTeachersWithAwards = [];
        $yesterdaysTeachersWithAwards = [];
        foreach ($teacherDonationAmountsByDay as $outerLoop) {
            if (isset($outerLoop['campaignaward_id'])) {
                //$this->logger->debug("Comparing Today: ".$todaysDate->format('Y-m-d H:i:s')." To outerLoop date: ".$outerLoop['donated_at']->format('Y-m-d H:i:s'));
                if ($todaysDate == $outerLoop['donated_at']) { //Today
                    //$this->logger->debug("Found award for today: ".print_r($outerLoop, true));
                    array_push($todaysTeachersWithAwards, $outerLoop);
                } elseif ($todaysDate > $outerLoop['donated_at']) { //Yesterday
                    $existsFlag = false;
                    //$this->logger->debug("Found award for yesterday: ".print_r($outerLoop, true));
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

    /**
     * Gets teacher ID and donation amounts by Dat
     * returns object.
     */
    public function getTeacherAwards(array $options)
    {
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
              //$this->logger->debug(print_r($teacher, true));
          }

        foreach ($teacherDonationAmountsByDay as $key => $outerLoop) {
            foreach ($teacherDonationAmountsByDay as $innerLoop) {
                //If award already happend on a previous day, we remove it
              if (!isset($outerLoop['campaignaward_id'])){
                  unset($teacherDonationAmountsByDay[$key]);
              }else if ($innerLoop['id'] == $outerLoop['id'] && $innerLoop['campaignaward_id'] == $outerLoop['campaignaward_id'] && $innerLoop['donated_at'] < $outerLoop['donated_at']) {
                  unset($teacherDonationAmountsByDay[$key]);
              }
            }
        }

          $this->logger->debug('Classes with Awards before today!');
          foreach ($teacherDonationAmountsByDay as $key => $outerLoop) {
              $this->logger->debug(print_r($teacherDonationAmountsByDay, true));
          }

        return $teacherDonationAmountsByDay;
    }

    public function convertToDay($inDate)
    {
        $dateString = $inDate->format('Y-m-d').' 00:00:00';

        return DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
    }
}
