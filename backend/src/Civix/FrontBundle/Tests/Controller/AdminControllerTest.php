<?php

namespace Civix\FrontBundle\Tests\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Superuser;
use Civix\FrontBundle\Tests\DataFixtures\ORM\LoadSuperuserData;

class AdminControllerTest extends WebTestCase
{
    public function testLogin()
    {
        $repository = $this->loadFixtures([LoadSuperuserData::class])
            ->getReferenceRepository();
        /** @var Superuser $user */
        $user = $repository->getReference('superuser_1');
        $client = $this->makeClient();
        $crawler = $client->request('GET', '/admin/login');
        $form = $crawler->selectButton('login')->form();
        $form['_username'] = $form['_password'] = $user->getUsername();
        $client->submit($form);
        $response = $client->getResponse();
        $this->assertEquals(302, $response->getStatusCode(), $response->getContent());
        $this->assertTrue(
            $response->headers->contains('Location', 'http://localhost/'),
            'redirect to homepage after successful login'
        );
    }

    public function testIndex()
    {
        $client = $this->makeClient();
        $client->request('GET', '/admin');
        $response = $client->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertContains('/admin/representatives', $response->headers->get('Location'));
    }
}