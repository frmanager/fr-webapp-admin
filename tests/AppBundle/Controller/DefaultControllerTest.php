<?php

namespace Tests\AppBundle\Controller;
use AppBundle\Utils\QueryHelper;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @coversDefaultClass \AppBundle\Controller\DefaultController
 */
class DefaultControllerTest extends WebTestCase
{


    /**
    * @covers ::indexAction
    */
    public function testIndex()
    {
        $client = static::createClient(array(),array('HTTPS' => true));

        $crawler = $client->request('GET', '/');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }


    /**
    * @covers ::indexAction
    */
    public function testIndexRedirect()
    {
        $client = static::createClient(array(),array('HTTPS' => true));

        $crawler = $client->request('GET', '/account/login');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
