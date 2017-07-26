<?php

// src/AppBundle/Utils/ValidationHelper.php

namespace AppBundle\Utils;

use AppBundle\Entity\Campaign;
use AppBundle\Form\UserType;
use AppBundle\Entity\User;
use AppBundle\Entity\CampaignUser;
use AppBundle\Entity\UserStatus;
use DateTime;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;


class CampaignHelper
{
  protected $em;
  protected $logger;

  public function __construct(EntityManager $em, Logger $logger)
  {
      $this->em = $em;
      $this->logger = $logger;
  }


  public function loadCampaign($data, $options){
    if (!isset($data['user'])) {
        $this->logger->debug('Cannot perform Load Campaign. User was not provided');
        return false;
    }else {
        return $this->createCampaign($data, $options);
    }
  }


  private function createCampaign($data, $options){

    $campaign = new Campaign();

    if (isset($data['campaign']['name'])) {
        $campaign->setName($data['campaign']['name']);
    } else {
        $campaign->setName('My First Campaign');
    }

    if (isset($data['campaign']['description'])) {
        $campaign->setDescription($data['campaign']['description']);
    } else {
        $campaign->setDescription('This is where the description will go for your campaign');
    }

    if (isset($data['campaign']['theme'])) {
        $campaign->setTheme($data['campaign']['theme']);
    } else {
        $campaign->setTheme('cerulean');
    }

    if (isset($data['campaign']['url'])) {
        $campaign->setUrl($data['campaign']['url']);
    } else {
        $campaign->setUrl($this->generateRandomString(12));
    }

    if (isset($data['campaign']['email'])) {
        $campaign->setEmail($data['campaign']['email']);
    } else {
        $campaign->setEmail($data['user']->getEmail());
    }

    if (isset($data['campaign']['fundingGoal'])) {
        $campaign->setFundingGoal($data['campaign']['fundingGoal']);
    } else {
        $campaign->setFundingGoal(10000);
    }


    if (isset($data['campaign']['createdBy'])) {
        $campaign->setCreatedBy($data['campaign']['createdBy']);
    } else {
        $campaign->setCreatedBy($data['user']);
    }

    if (isset($data['campaign']['startDate'])) {
        $campaign->setStartDate($data['campaign']['startDate']);
    } else {
      $date = new DateTime();
      $date->modify('-1 month');
      $campaign->setStartDate($date);
    }


    if (isset($data['campaign']['endDate'])) {
        $campaign->setEndDate($data['campaign']['endDate']);
    } else {
      $date = new DateTime();
      $date->modify('+2 month');
      $campaign->setEndDate($date);
    }

    //Save
    $this->em->persist($campaign);

    //Now we add the campaignUser record
    $this->createCampaignUser($campaign, $data['user']);

    //$this->createDefaultGrades($campaign);


    #flush
    $this->em->flush();

    #Return the campaign to be used later
    return $campaign;
  }

  private function createCampaignUser($campaign, $user){
    //Now we need to add the user as a member of the campaign
    $campaignUser = new CampaignUser();
    $campaignUser->setUser($user);
    $campaignUser->setCampaign($campaign);
    //Save
    $this->em->persist($campaignUser);
  }



  private function generateRandomString($length = 10) {
      $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $charactersLength = strlen($characters);
      $randomString = '';
      for ($i = 0; $i < $length; $i++) {
          $randomString .= $characters[rand(0, $charactersLength - 1)];
      }
      return $randomString;
  }


  private function generateRandomDate($min_date, $max_date) {
      /* Gets 2 dates as string, earlier and later date.
         Returns date in between them.
      */

      $min_epoch = strtotime($min_date);
      $max_epoch = strtotime($max_date);

      $rand_epoch = rand($min_epoch, $max_epoch);

      return new DateTime('Y-m-d H:i:s', $rand_epoch);
  }


}
