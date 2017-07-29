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
     * Dev/Test/Prod
     */
    private $environment;

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

        $this->environment = static::$kernel->getContainer()
            ->getParameter("kernel.environment");

    }

    public function testCampaignCreation()
    {
      $this->logger->debug("running test: testCampaignCreation");
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
          'name' => 'testCampaignCreation',
          'description' => 'The Campaign description field',
          'theme' => 'superhero',
          'email' => 'thisisatest@gmail.com',
          'fundingGoal' => 1500,
          'createdBy' => $user,
          'startDate' => $campaignStartDate,
          'endDate' => $campaignEndDate,
        ));

        $campaignHelper = new CampaignHelper($this->em, $this->logger, $this->environment);
        $campaign = $campaignHelper->loadCampaign($data, array());

        $this->assertEquals($campaign->getCreatedBy(), $user);
        $this->assertEquals($campaign->getEmail(), 'thisisatest@gmail.com');
        $this->assertEquals($campaign->getName(), 'testCampaignCreation');
        $this->assertEquals($campaign->getFundingGoal(), 1500);
        $this->assertEquals($campaign->getDescription(), 'The Campaign description field');
        $this->assertEquals($campaign->getStartDate(), $campaignStartDate);
        $this->assertEquals($campaign->getEndDate(), $campaignEndDate);
        $this->assertEquals($campaign->getTheme(), 'superhero');

        $campaignUser = $this->em->getRepository('AppBundle:CampaignUser')->findOneBy(array('campaign'=>$campaign, 'user'=>$user));
        $this->assertNotNull($campaignUser);
    }

    /*
    *
    * If no data is provided, the default values are taken
    *
    */
    public function testDefaultCampaignCreation()
    {
      $this->logger->debug("running test: testDefaultCampaignCreation");
      $user = $this->em->getRepository('AppBundle:User')->findOneByEmail('davidlarrimore@gmail.com');

      $data = array('user' => $user);

        $campaignHelper = new CampaignHelper($this->em, $this->logger, $this->environment);
        $campaign = $campaignHelper->loadCampaign($data, array());

        $this->assertEquals($campaign->getCreatedBy(), $user);
        $this->assertEquals($campaign->getEmail(), "thisisatest@gmail.com");
        $this->assertEquals($campaign->getName(), 'My First Campaign');
        $this->assertEquals($campaign->getFundingGoal(), 10000);
        $this->assertEquals($campaign->getDescription(), 'This is where the description will go for your campaign');
        $this->assertNotNull($campaign->getStartDate());
        $this->assertNotNull($campaign->getEndDate());
        $this->assertEquals($campaign->getTheme(), 'cerulean');

        $campaignUser = $this->em->getRepository('AppBundle:CampaignUser')->findOneBy(array('campaign'=>$campaign, 'user'=>$user));
        $this->assertNotNull($campaignUser);
    }


    /*
    *
    * If no user is provided, it will fail
    *
    */
    public function testNoUserProvidedCreation()
    {
      $this->logger->debug("running test: testNoUserProvidedCreation");

      $data = array(
        'campaign' => array(
          'name' => 'testNoUserProvidedCreation',
          'description' => 'The Campaign description field',
          'theme' => 'superhero',
          'email' => 'thisisatest@gmail.com',
          'fundingGoal' => 1500,
        ));

        $campaignHelper = new CampaignHelper($this->em, $this->logger, $this->environment);
        $campaign = $campaignHelper->loadCampaign($data, array());
        $this->assertFalse($campaign);

    }


    /*
    *
    * Some variables provided, some not provided. should see "Some" defaults
    *
    */
    public function testPartialCampaign()
    {
      $this->logger->debug("running test: testPartialCampaign");

      $user = $this->em->getRepository('AppBundle:User')->findOneByEmail('davidlarrimore@gmail.com');

      $data = array(
        'user' => $user,
        'campaign' => array(
          'name' => 'testPartialCampaign',
          'email' => 'thisisatest@gmail.com',
          'createdBy' => $user,
        ));

        $campaignHelper = new CampaignHelper($this->em, $this->logger, $this->environment);
        $campaign = $campaignHelper->loadCampaign($data, array());

        $this->assertEquals($campaign->getCreatedBy(), $user);
        $this->assertEquals($campaign->getEmail(), 'thisisatest@gmail.com');
        $this->assertEquals($campaign->getName(), 'testPartialCampaign');
        $this->assertEquals($campaign->getFundingGoal(), 10000);
        $this->assertEquals($campaign->getDescription(), 'This is where the description will go for your campaign');
        $this->assertNotNull($campaign->getStartDate());
        $this->assertNotNull($campaign->getEndDate());
        $this->assertEquals($campaign->getTheme(), 'cerulean');

        $campaignUser = $this->em->getRepository('AppBundle:CampaignUser')->findOneBy(array('campaign'=>$campaign, 'user'=>$user));
        $this->assertNotNull($campaignUser);

    }



    /*
    *
    * Testing all 4 combinations of campaign awards student/teacher place/level
    *
    */
    public function testCampaignAwards()
    {
      $this->logger->debug("running test: testCampaignAwards");

      $user = $this->em->getRepository('AppBundle:User')->findOneByEmail('davidlarrimore@gmail.com');
      $campaignAwardTypeTeacher = $this->em->getRepository('AppBundle:Campaignawardtype')->findOneByValue('teacher');
      $campaignAwardTypeStudent = $this->em->getRepository('AppBundle:Campaignawardtype')->findOneByValue('student');
      $campaignAwardStyleLevel = $this->em->getRepository('AppBundle:Campaignawardstyle')->findOneByValue('level');
      $campaignAwardStylePlace = $this->em->getRepository('AppBundle:Campaignawardstyle')->findOneByValue('place');


      $data = array(
        'user' => $user,
        'campaign' => array(
          'name' => 'testCampaignAwards',
          'email' => 'thisisatest@gmail.com',
          'createdBy' => $user,
          'campaignawards' => array(
            0 => array(
              'campaignawardtype' => $campaignAwardTypeTeacher,
              'campaignawardstyle' => $campaignAwardStyleLevel,
              'name' => 'Teacher Level Award',
              'amount' => 100
            ),
            1 => array(
              'campaignawardtype' => $campaignAwardTypeTeacher,
              'campaignawardstyle' => $campaignAwardStylePlace,
              'name' => 'Teacher Place Award',
              'place' => 1
            ),
            2 => array(
              'campaignawardtype' => $campaignAwardTypeStudent,
              'campaignawardstyle' => $campaignAwardStyleLevel,
              'name' => 'Student Level Award',
              'amount' => 100
            ),
            3 => array(
              'campaignawardtype' => $campaignAwardTypeStudent,
              'campaignawardstyle' => $campaignAwardStylePlace,
              'name' => 'Student Place Award',
              'place' => 1
            )
          )
        ));

        $campaignHelper = new CampaignHelper($this->em, $this->logger, $this->environment);
        $campaign = $campaignHelper->loadCampaign($data, array());
        $this->assertNotNull($campaign);

        $campaignUser = $this->em->getRepository('AppBundle:CampaignUser')->findOneBy(array('campaign'=>$campaign, 'user'=>$user));
        $this->assertNotNull($campaignUser);

        $campaignAwards = $campaign->getCampaignAwards();
        $campaignUsers = $campaign->getCampaignUsers();
        $this->assertNotNull($campaignUsers);
        $this->assertNotNull($campaignAwards);

        $query = $this->em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from('AppBundle:Campaignaward', 'u')
            ->where('u.campaign = :campaignId')
            ->setParameter('campaignId', $campaign->getId())
            ->getQuery();

        $total = $query->getSingleScalarResult();
        $this->assertEquals(4, $total);

    }


    /*
    *
    * level awards require amount.....place provided....should fail (false)
    *
    */
    public function testCampaignAwardsBadLevelTeacherCombo()
    {
      $this->logger->debug("running test: testCampaignAwardsBadLevelTeacherCombo");
      $user = $this->em->getRepository('AppBundle:User')->findOneByEmail('davidlarrimore@gmail.com');
      $campaignAwardTypeTeacher = $this->em->getRepository('AppBundle:Campaignawardtype')->findOneByValue('teacher');
      $campaignAwardStyleLevel = $this->em->getRepository('AppBundle:Campaignawardstyle')->findOneByValue('level');

      $data = array(
        'user' => $user,
        'campaign' => array(
          'name' => 'testCampaignAwards',
          'email' => 'thisisatest@gmail.com',
          'createdBy' => $user,
          'campaignawards' => array(
            0 => array(
              'campaignawardtype' => $campaignAwardTypeTeacher,
              'campaignawardstyle' => $campaignAwardStyleLevel,
              'name' => 'Teacher Level Award',
              'place' => 3
            )
          )
        ));

        $campaignHelper = new CampaignHelper($this->em, $this->logger, $this->environment);
        $campaign = $campaignHelper->loadCampaign($data, array());
        $this->assertFalse($campaign);

    }


    /*
    *
    * place awards require place.....amount provided....should fail (false)
    *
    */
    public function testCampaignAwardsBadPlaceTeacherCombo()
    {
      $this->logger->debug("running test: testCampaignAwardsBadPlaceTeacherCombo");
      $user = $this->em->getRepository('AppBundle:User')->findOneByEmail('davidlarrimore@gmail.com');
      $campaignAwardTypeTeacher = $this->em->getRepository('AppBundle:Campaignawardtype')->findOneByValue('teacher');
      $campaignAwardStylePlace = $this->em->getRepository('AppBundle:Campaignawardstyle')->findOneByValue('place');

      $data = array(
        'user' => $user,
        'campaign' => array(
          'name' => 'testCampaignAwardsBadPlaceTeacherCombo',
          'email' => 'thisisatest@gmail.com',
          'createdBy' => $user,
          'campaignawards' => array(
            0 => array(
              'campaignawardtype' => $campaignAwardTypeTeacher,
              'campaignawardstyle' => $campaignAwardStylePlace,
              'name' => 'Teacher Place Award',
              'amount' => 100
            )
          )
        ));

        $campaignHelper = new CampaignHelper($this->em, $this->logger, $this->environment);
        $campaign = $campaignHelper->loadCampaign($data, array());
        $this->assertFalse($campaign);
    }

    /*
    *
    * level awards require amount.....place provided....should fail (false)
    *
    */
    public function testCampaignAwardsBadLevelStudentCombo()
    {
      $this->logger->debug("running test: testCampaignAwardsBadLevelStudentCombo");
      $user = $this->em->getRepository('AppBundle:User')->findOneByEmail('davidlarrimore@gmail.com');
      $campaignAwardTypeStudent = $this->em->getRepository('AppBundle:Campaignawardtype')->findOneByValue('student');
      $campaignAwardStyleLevel = $this->em->getRepository('AppBundle:Campaignawardstyle')->findOneByValue('level');

      $data = array(
        'user' => $user,
        'campaign' => array(
          'name' => 'testCampaignAwardsBadLevelStudentCombo',
          'email' => 'thisisatest@gmail.com',
          'createdBy' => $user,
          'campaignawards' => array(
            0 => array(
              'campaignawardtype' => $campaignAwardTypeStudent,
              'campaignawardstyle' => $campaignAwardStyleLevel,
              'name' => 'Student Level Award',
              'place' => 3
            )
          )
        ));

        $campaignHelper = new CampaignHelper($this->em, $this->logger, $this->environment);
        $campaign = $campaignHelper->loadCampaign($data, array());
        $this->assertFalse($campaign);
    }


    /*
    *
    * No CampaignAward Name provided
    *
    */
    public function testCampaignAwardsNoName()
    {
      $this->logger->debug("running test: testCampaignAwardsNoName");
      $user = $this->em->getRepository('AppBundle:User')->findOneByEmail('davidlarrimore@gmail.com');
      $campaignAwardTypeStudent = $this->em->getRepository('AppBundle:Campaignawardtype')->findOneByValue('student');
      $campaignAwardStylePlace = $this->em->getRepository('AppBundle:Campaignawardstyle')->findOneByValue('place');

      $data = array(
        'user' => $user,
        'campaign' => array(
          'name' => 'testCampaignAwardsNoName',
          'email' => 'thisisatest@gmail.com',
          'createdBy' => $user,
          'campaignawards' => array(
            0 => array(
              'campaignawardtype' => $campaignAwardTypeStudent,
              'campaignawardstyle' => $campaignAwardStylePlace,
              'amount' => 100
            )
          )
        ));

        $campaignHelper = new CampaignHelper($this->em, $this->logger, $this->environment);
        $campaign = $campaignHelper->loadCampaign($data, array());
        $this->assertFalse($campaign);

    }


    /*
    *
    * place awards require place.....amount provided....should fail (false)
    *
    */
    public function testCampaignAwardsBadPlaceStudentCombo()
    {
      $this->logger->debug("running test: testCampaignAwardsBadPlaceStudentCombo");
      $user = $this->em->getRepository('AppBundle:User')->findOneByEmail('davidlarrimore@gmail.com');
      $campaignAwardTypeStudent = $this->em->getRepository('AppBundle:Campaignawardtype')->findOneByValue('student');
      $campaignAwardStylePlace = $this->em->getRepository('AppBundle:Campaignawardstyle')->findOneByValue('place');

      $data = array(
        'user' => $user,
        'campaign' => array(
          'name' => 'testCampaignAwardsBadPlaceStudentCombo',
          'email' => 'thisisatest@gmail.com',
          'createdBy' => $user,
          'campaignawards' => array(
            0 => array(
              'campaignawardtype' => $campaignAwardTypeStudent,
              'campaignawardstyle' => $campaignAwardStylePlace,
              'name' => 'Student Place Award',
              'amount' => 100
            )
          )
        ));

        $campaignHelper = new CampaignHelper($this->em, $this->logger, $this->environment);
        $campaign = $campaignHelper->loadCampaign($data, array());
        $this->assertFalse($campaign);

    }


    /*
    *
    * testing creating a single campaign award, vs an array of multiple
    *
    */
    public function testCampaignAwardsSingle()
    {
      $this->logger->debug("running test: testCampaignAwardsSingle");
      $user = $this->em->getRepository('AppBundle:User')->findOneByEmail('davidlarrimore@gmail.com');
      $campaignAwardTypeTeacher = $this->em->getRepository('AppBundle:Campaignawardtype')->findOneByValue('teacher');
      $campaignAwardStyleLevel = $this->em->getRepository('AppBundle:Campaignawardstyle')->findOneByValue('level');

      $data = array(
        'user' => $user,
        'campaign' => array(
          'name' => 'testCampaignAwardsSingle',
          'email' => 'thisisatest@gmail.com',
          'createdBy' => $user,
          'campaignawards' => array(
            'campaignawardtype' => $campaignAwardTypeTeacher,
            'campaignawardstyle' => $campaignAwardStyleLevel,
            'name' => 'Teacher Level Award',
            'amount' => 300
            )
          )
        );

        $campaignHelper = new CampaignHelper($this->em, $this->logger, $this->environment);
        $campaign = $campaignHelper->loadCampaign($data, array());
        $campaignAwards = $campaign->getCampaignAwards();
        $campaignUsers = $campaign->getCampaignUsers();
        $this->assertNotNull($campaignUsers);
        $this->assertNotNull($campaignAwards);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        $campaigns = $this->em->getRepository('AppBundle:Campaign')->findByEmail('thisisatest@gmail.com');

        foreach($campaigns as $campaign){
          $this->em->remove($campaign);
        }
        $this->em->flush();

        $this->em->close();
        $this->em = null; // avoid memory leaks
    }

}
