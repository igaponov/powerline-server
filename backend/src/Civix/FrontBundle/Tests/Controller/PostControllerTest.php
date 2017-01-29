<?php

namespace Civix\FrontBundle\Tests\Controller\Superuser;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSpamPostData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSuperuserData;

class PostControllerTest extends WebTestCase
{
    public function testIndexPage()
    {
        $this->loadFixtures([
            LoadSuperuserData::class,
            LoadSpamPostData::class,
        ]);
        $client = $this->createClient([], ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => 'admin']);
        $crawler = $client->request('GET', '/superuser/posts');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertCount(3, $crawler->filter('tbody > tr'));
        $this->assertCount(1, $crawler->filter('input[value="Remove post"]'));
        $this->assertCount(3, $crawler->filter('input[value="Remove user"]'));
    }
}