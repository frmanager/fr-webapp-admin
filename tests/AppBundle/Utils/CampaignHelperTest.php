<?php

namespace Tests\AppBundle\Utils;

use AppBundle\Utils\QueryHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use AppBundle\Entity\Campaign;
use AppBundle\Entity\UserStatus;
use DateTime;
use AppBundle\Utils\CampaignHelper;
use Monolog\Logger;

class CampaignHelperTest extends KernelTestCase
{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Monolog\Logger
     */
    private $logger;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        self::bootKernel();

        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->logger = static::$kernel->getContainer()
            ->get('logger');
    }

    public function testCampaignCreation()
    {

      $date = new DateTime();
      $date->modify('-1 month');
      $campaignStartDate = $date;

      $date = new DateTime();
      $date->modify('+2 months');
      $campaignEndDate = $date;

      $user = $this->em->getRepository('AppBundle:User')->findOneByEmail('davidlarrimore@gmail.com');

      $data = array(
        'user' => $user,
        'campaign' => array(
          'name' => 'New Campaign',
          'description' => 'The Campaign description field',
          'theme' => 'superhero',
          'email' => 'letstrythis@gmail.com',
          'fundingGoal' => 1500,
          'createdBy' => $user,
          'startDate' => $campaignStartDate,
          'endDate' => $campaignEndDate,
        ));

        $campaignHelper = new CampaignHelper($this->em, $this->logger);
        $campaign = $campaignHelper->loadCampaign($data, array());

        $this->assertEquals($campaign->getCreatedBy(), $user);
        $this->assertEquals($campaign->getEmail(), 'letstrythis@gmail.com');
        $this->assertEquals($campaign->getName(), 'New Campaign');
        $this->assertEquals($campaign->getFundingGoal(), 1500);
        $this->assertEquals($campaign->getDescription(), 'The Campaign description field');
        $this->assertEquals($campaign->getStartDate(), $campaignStartDate);
        $this->assertEquals($campaign->getEndDate(), $campaignEndDate);
        $this->assertEquals($campaign->getTheme(), 'superhero');

        $campaignUser = $this->em->getRepository('AppBundle:CampaignUser')->findOneBy(array('campaign'=>$campaign, 'user'=>$user));
        $this->assertNotNull($campaignUser);

        $this->em->remove($campaignUser);
        $this->em->remove($campaign);
        $this->em->flush();
    }

    /*
    *
    * If no data is provided, the default values are taken
    *
    */
    public function testDefaultCampaignCreation()
    {

      $user = $this->em->getRepository('AppBundle:User')->findOneByEmail('davidlarrimore@gmail.com');

      $data = array('user' => $user);

        $campaignHelper = new CampaignHelper($this->em, $this->logger);
        $campaign = $campaignHelper->loadCampaign($data, array());

        $this->assertEquals($campaign->getCreatedBy(), $user);
        $this->assertEquals($campaign->getEmail(), $user->getEmail());
        $this->assertEquals($campaign->getName(), 'My First Campaign');
        $this->assertEquals($campaign->getFundingGoal(), 10000);
        $this->assertEquals($campaign->getDescription(), 'This is where the description will go for your campaign');
        $this->assertNotNull($campaign->getStartDate());
        $this->assertNotNull($campaign->getEndDate());
        $this->assertEquals($campaign->getTheme(), 'cerulean');

        $campaignUser = $this->em->getRepository('AppBundle:CampaignUser')->findOneBy(array('campaign'=>$campaign, 'user'=>$user));
        $this->assertNotNull($campaignUser);

        $this->em->remove($campaignUser);
        $this->em->remove($campaign);
        $this->em->flush();
    }


    /*
    *
    * If no user is provided, it will fail
    *
    */
    public function testNoUserProvidedCreation()
    {


      $data = array(
        'campaign' => array(
          'name' => 'New Campaign',
          'description' => 'The Campaign description field',
          'theme' => 'superhero',
          'email' => 'letstrythis@gmail.com',
          'fundingGoal' => 1500,
        ));

        $campaignHelper = new CampaignHelper($this->em, $this->logger);
        $campaign = $campaignHelper->loadCampaign($data, array());

        $this->assertFalse($campaign);

    }



    public function testPartialCampaign()
    {


      $user = $this->em->getRepository('AppBundle:User')->findOneByEmail('davidlarrimore@gmail.com');

      $data = array(
        'user' => $user,
        'campaign' => array(
          'name' => 'New Campaign',
          'email' => 'letstrythis@gmail.com',
          'createdBy' => $user,
        ));

        $campaignHelper = new CampaignHelper($this->em, $this->logger);
        $campaign = $campaignHelper->loadCampaign($data, array());

        $this->assertEquals($campaign->getCreatedBy(), $user);
        $this->assertEquals($campaign->getEmail(), 'letstrythis@gmail.com');
        $this->assertEquals($campaign->getName(), 'New Campaign');
        $this->assertEquals($campaign->getFundingGoal(), 10000);
        $this->assertEquals($campaign->getDescription(), 'This is where the description will go for your campaign');
        $this->assertNotNull($campaign->getStartDate());
        $this->assertNotNull($campaign->getEndDate());
        $this->assertEquals($campaign->getTheme(), 'cerulean');

        $campaignUser = $this->em->getRepository('AppBundle:CampaignUser')->findOneBy(array('campaign'=>$campaign, 'user'=>$user));
        $this->assertNotNull($campaignUser);

        $this->em->remove($campaignUser);
        $this->em->remove($campaign);
        $this->em->flush();
    }


    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->em->close();
        $this->em = null; // avoid memory leaks
    }

}
