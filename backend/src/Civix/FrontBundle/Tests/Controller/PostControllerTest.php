<?php

namespace Civix\FrontBundle\Tests\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSpamPostData;
use Civix\FrontBundle\Tests\DataFixtures\ORM\LoadSuperuserData;
use Doctrine\DBAL\Connection;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;

class PostControllerTest extends WebTestCase
{
    public function testIndexPage()
    {
        $this->loadFixtures([
            LoadSuperuserData::class,
            LoadSpamPostData::class,
        ]);
        $client = $this->makeClient(true);
        $crawler = $client->request('GET', '/admin/posts');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertCount(3, $crawler->filter('tbody > tr'));
        $this->assertCount(1, $crawler->filter('table button:contains("Remove post")'));
        $this->assertCount(3, $crawler->filter('table button:contains("Ban user")'));
    }

    public function testDeletePost()
    {
        $repository = $this->loadFixtures([
            LoadSuperuserData::class,
            LoadSpamPostData::class,
        ])->getReferenceRepository();
        $post = $repository->getReference('post_4');
        $client = $this->makeClient(true);
        $crawler = $client->request('GET', '/admin/posts');
        $form = $crawler->selectButton('Remove post')->form();
        $client->submit($form);
        $response = $client->getResponse();
        $this->assertEquals(302, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM user_posts up WHERE up.id = ?', [$post->getId()]);
        $this->assertEquals(0, $count);
    }

    public function testDeletePosts()
    {
        $this->loadFixtures([
            LoadSuperuserData::class,
            LoadSpamPostData::class,
        ]);
        $client = $this->makeClient(true);
        $crawler = $client->request('GET', '/admin/posts');
        $options = $crawler->filter('input[name="post[]"]')->extract(['value']);
        $form = $crawler->selectButton('Remove posts')->form();
        /** @var ChoiceFormField $field */
        $document = new \DOMDocument();
        foreach ($options as $option) {
            $element = $document->createElement('input', $option);
            $element->setAttribute('type', 'checkbox');
            $element->setAttribute('name', 'post[]');
            $element->setAttribute('checked', 'checked');
            $field = new ChoiceFormField($element);
            $form->set($field);
        }
        $client->submit($form);
        $response = $client->getResponse();
        $this->assertEquals(302, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('
            SELECT COUNT(*) FROM user_posts up
            WHERE up.id IN ('.implode(',', array_fill(1, count($options), '?')).')', $options);
        $this->assertEquals(count($options) - 1, $count);
    }

    public function testBanPostAuthors()
    {
        $this->loadFixtures([
            LoadSuperuserData::class,
            LoadSpamPostData::class,
        ]);
        $client = $this->makeClient(true);
        $crawler = $client->request('GET', '/admin/posts');
        $options = $crawler->filter('input[name="post[]"]')->extract(['value']);
        $form = $crawler->selectButton('Ban users')->form();
        /** @var ChoiceFormField $field */
        $document = new \DOMDocument();
        foreach ($options as $option) {
            $element = $document->createElement('input', $option);
            $element->setAttribute('type', 'checkbox');
            $element->setAttribute('name', 'post[]');
            $element->setAttribute('checked', 'checked');
            $field = new ChoiceFormField($element);
            $form->set($field);
        }
        $client->submit($form);
        $response = $client->getResponse();
        $this->assertEquals(302, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('
            SELECT COUNT(DISTINCT u.id) FROM user u
            LEFT JOIN user_posts up ON u.id = up.user_id
            WHERE u.enabled = 0 AND up.id IN ('.implode(',', array_fill(1, count($options), '?')).')', $options);
        $this->assertCount((int)$count, $options);
    }

    public function testBanPostAuthor()
    {
        $repository = $this->loadFixtures([
            LoadSuperuserData::class,
            LoadSpamPostData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('user_1');
        $client = $this->makeClient(true);
        $crawler = $client->request('GET', '/admin/posts');
        $form = $crawler->selectButton('Ban user')->form();
        $client->submit($form);
        $response = $client->getResponse();
        $this->assertEquals(302, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM user u WHERE u.enabled = 0 AND id = ?', [$user->getId()]);
        $this->assertEquals(1, $count);
    }
}