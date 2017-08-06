<?php

// tests/AppBundle/Controller/DefaultControllerTest.php
namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use AppBundle\Entity\Campaign;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class RegisterControllerTest extends WebTestCase
{
    private $client = null;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;


    /**
     * @var \Monolog\Logger
     */
    private $logger;


    public function setUp()
    {
        $this->client = static::createClient();

        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->logger = static::$kernel->getContainer()
            ->get('logger');

            $campaign = $this->em->getRepository('AppBundle:Campaign')->findOneByEmail('thisisatest@gmail.com');
            if (!empty($campaign)){
              $students = $this->em->getRepository('AppBundle:Student')->findByCampaign($campaign);
              foreach($students as $student){
                $this->em->remove($student);
              }

              $classrooms = $this->em->getRepository('AppBundle:Classroom')->findByCampaign($campaign);
              foreach($classrooms as $classroom){
                $this->em->remove($classroom);
              }

              $grades = $this->em->getRepository('AppBundle:Grade')->findByCampaign($campaign);
              foreach($grades as $grade){
                $this->em->remove($grade);
              }

              $this->em->remove($campaign);

              $user = $this->em->getRepository('AppBundle:User')->findOneByEmail('thisisatest@gmail.com');
              if (!empty($user)){
                $this->em->remove($user);
              }

              $this->em->flush();

            }






    }


    public function testRegisterPage()
    {
        //$this->logIn();
        $crawler = $this->client->request('GET', '/account/signup');

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        //$this->assertSame('Admin Dashboard', $crawler->filter('h1')->text());
    }


    public function testRegistration()
    {

        $csrfToken = $this->client->getContainer()->get('security.csrf.token_manager')->getToken('user_type');
        $this->client->followRedirects();
        $crawler = $this->client->request('POST', '/account/signup', array(
          'user' => array(
            'firstName' => 'David',
            'lastName' => 'Larrimore',
            'email' => 'thisisatest@gmail.com',
            'defaultCampaignId' => null,
            'Password' => array(
              'second' => 'thisisatest',
              'first' => 'thisisatest',
            ),
            '_token' => $csrfToken
          )
        ));


        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());


        $campaign = $this->em->getRepository('AppBundle:Campaign')->findOneByEmail('thisisatest@gmail.com');

        $students = $this->em->getRepository('AppBundle:Student')->findByCampaign($campaign);
        foreach($students as $student){
          $this->em->remove($student);
        }

        $classrooms = $this->em->getRepository('AppBundle:Classroom')->findByCampaign($campaign);
        foreach($classrooms as $classroom){
          $this->em->remove($classroom);
        }

        $grades = $this->em->getRepository('AppBundle:Grade')->findByCampaign($campaign);
        foreach($grades as $grade){
          $this->em->remove($grade);
        }

        $this->em->remove($campaign);

        $user = $this->em->getRepository('AppBundle:User')->findOneByEmail('thisisatest@gmail.com');
        $this->em->remove($user);

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
