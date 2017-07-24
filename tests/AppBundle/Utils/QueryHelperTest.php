<?php

namespace Tests\AppBundle\Utils;

use AppBundle\Utils\QueryHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use AppBundle\Entity\Campaign;
use AppBundle\Entity\UserStatus;

class QueryHelperTest extends KernelTestCase
{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        self::bootKernel();

        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

    }

    public function testSearchByCategoryName()
    {
      $userStatuses = $this->em->getRepository('AppBundle:UserStatus')->findAll();
      $this->assertCount(4, $userStatuses);
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
