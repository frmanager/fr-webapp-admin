<?php

// src/AppBundle/Utils/ValidationHelper.php

namespace AppBundle\Utils;

use AppBundle\Entity\Campaign;
use AppBundle\Entity\Campaignaward;
use AppBundle\Form\UserType;
use AppBundle\Entity\User;
use AppBundle\Entity\CampaignUser;
use AppBundle\Entity\UserStatus;
use DateTime;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\HttpKernel\Kernel as kernel;


class CampaignHelper
{
  protected $em;
  protected $logger;
  protected $environment;

  public function __construct(EntityManager $em, Logger $logger, $environment = null)
  {
      $this->em = $em;
      $this->logger = $logger;
      $this->environment = $environment;
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
    } else if ($this->environment === "test") {
        $campaign->setEmail('thisisatest@gmail.com');
    }else{
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
    $campaignUser = $this->createCampaignUser($campaign, $data);

    //If campaign awards are provided, create campaign awards
    if(!$campaignUser){
        $this->em->remove($campaign);
        return false;
    }


    //If campaign awards are provided, create campaign awards
    if(isset($data['campaign']['campaignawards'])){
      if(!$this->createCampaignAwards($campaign, $data)){
        $this->logger->debug("Create Campaignawards failed; removing CampaignUsers and Campaign");
        $this->em->remove($campaignUser);
        $this->em->remove($campaign);
        return false;
      }
    }else{
      $this->logger->debug("Skipping creating Campaignawards: none provided");
    }


    #flush
    $this->em->flush();

    #Return the campaign to be used later
    return $campaign;
  }

  public function createCampaignUser(Campaign $campaign, $data){
    //Now we need to add the user as a member of the campaign
    $campaignUser = new CampaignUser();
    $campaignUser->setUser($data['user']);
    $campaignUser->setCampaign($campaign);
    //Save
    $this->em->persist($campaignUser);
    $this->logger->debug("CampaignUser creation successful");
    return $campaignUser;

  }


  public function createCampaignAwards(Campaign $campaign, $data){
    //Now we need to add the user as a member of the campaign

    //check to see if Array
    if(isset($data['campaign']['campaignawards'][0]) && is_array($data['campaign']['campaignawards'][0])){
      foreach($data['campaign']['campaignawards'] as $campaignAward){
        if(!$this->createCampaignAward($campaign, $campaignAward)){
          return false;
        }
      }
    }else{
      if(!$this->createCampaignAward($campaign, $data['campaign']['campaignawards'])){
        return false;
      }
    }

    return true;
  }

  public function createCampaignAward(Campaign $campaign, $campaignAward){

    if(!isset($campaignAward['campaignawardstyle'])){
      $this->logger->debug("Campaignaward creation failure: Campaignawardstyle was not provided");
      return false;
    }else if(!isset($campaignAward['campaignawardtype'])){
      $this->logger->debug("Campaignaward creation failure: Campaignaward Campaignawardtype was not provided");
      return false;
    }else if(!isset($campaignAward['name'])){
      $this->logger->debug("Campaignaward creation failure: Campaignaward name was not provided");
      return false;
    }

    if($campaignAward['campaignawardstyle']->getValue() == "level" && (!isset($campaignAward['amount']) || $campaignAward['amount'] === 0)){
      $this->logger->debug("Campaignaward creation failure: Campaignawardstyle of level requires an amount");
      return false;
    }else if($campaignAward['campaignawardstyle']->getValue() == "place" && (!isset($campaignAward['place']) || $campaignAward['place'] === 0)){
      $this->logger->debug("Campaignaward creation failure: Campaignawardstyle of place requires an place");
      return false;
    }

    //Now we need to add the user as a member of the campaign
    $newCampaignAward = new Campaignaward();
    $newCampaignAward->setCampaign($campaign);
    $newCampaignAward->setName($campaignAward['name']);
    $newCampaignAward->setCampaignawardtype($campaignAward['campaignawardtype']);
    $newCampaignAward->setCampaignawardstyle($campaignAward['campaignawardstyle']);


    if(isset($campaignAward['place'])){
      $newCampaignAward->setPlace($campaignAward['place']);
    }

    if(isset($campaignAward['amount'])){
      $newCampaignAward->setAmount($campaignAward['amount']);
    }

    if(isset($campaignAward['description'])){
      $newCampaignAward->setDescription($campaignAward['description']);
    }

    //Save
    $this->em->persist($newCampaignAward);

    return $newCampaignAward;
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
