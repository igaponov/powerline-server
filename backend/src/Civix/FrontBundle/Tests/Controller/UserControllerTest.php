<?php

namespace Civix\FrontBundle\Tests\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSpamPostData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSpamUserPetitionData;
use Civix\FrontBundle\Tests\DataFixtures\ORM\LoadSuperuserData;

class UserControllerTest extends WebTestCase
{
    public function testUserPosts()
    {
        $repository = $this->loadFixtures([
            LoadSuperuserData::class,
            LoadSpamPostData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('user_3');
        $client = $this->makeClient(true);
        $uri = str_replace('{id}', $user->getId(), '/admin/users/{id}/posts');
        $crawler = $client->request('GET', $uri);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertCount(3, $crawler->filter('tbody > tr'));
        $this->assertCount(1, $crawler->filter('table button:contains("Remove post")'));
        $this->assertCount(3, $crawler->filter('table button:contains("Ban user")'));
    }

    public function testUserPetitions()
    {
        $repository = $this->loadFixtures([
            LoadSuperuserData::class,
            LoadSpamUserPetitionData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('user_3');
        $client = $this->makeClient(true);
        $uri = str_replace('{id}', $user->getId(), '/admin/users/{id}/petitions');
        $crawler = $client->request('GET', $uri);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertCount(3, $crawler->filter('tbody > tr'));
        $this->assertCount(1, $crawler->filter('table button:contains("Remove petition")'));
        $this->assertCount(3, $crawler->filter('table button:contains("Ban user")'));
    }
}