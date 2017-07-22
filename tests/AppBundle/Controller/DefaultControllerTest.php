<?php

namespace Tests\AppBundle\Controller;
use AppBundle\Utils\QueryHelper;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testIndexRedirect()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/account/login');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
