<?php

// tests/AppBundle/Controller/DefaultControllerTest.php
namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;


/**
 * @coversDefaultClass \AppBundle\Controller\SecurityController
 */
class SecurityControllerTest extends WebTestCase
{
    private $client = null;

    public function setUp()
    {
        $this->client = static::createClient(array(),array('HTTPS' => true));
    }




    /**
    * @covers ::loginAction
    */
    public function testLogin()
    {
        $this->logIn();
        $crawler = $this->client->request('GET', '/');

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        //$this->assertSame('Admin Dashboard', $crawler->filter('h1')->text());
    }


    private function logIn()
    {

      $this->client = static::createClient(array(),array('HTTPS' => true));
      $container = static::$kernel->getContainer();
      $session = $container->get('session');
      $user = self::$kernel->getContainer()->get('doctrine')->getRepository('AppBundle:User')->findOneByEmail('davidlarrimore@gmail.com');

      $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
      $session->set('_security_main', serialize($token));
      $session->save();

      $this->client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));
    }
}
