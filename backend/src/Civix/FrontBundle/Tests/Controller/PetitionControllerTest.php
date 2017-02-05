<?php

namespace Civix\FrontBundle\Tests\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSpamUserPetitionData;
use Civix\FrontBundle\Tests\DataFixtures\ORM\LoadSuperuserData;
use Doctrine\DBAL\Connection;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;

class PetitionControllerTest extends WebTestCase
{
    public function testIndexPage()
    {
        $this->loadFixtures([
            LoadSuperuserData::class,
            LoadSpamUserPetitionData::class,
        ]);
        $client = $this->makeClient(true);
        $crawler = $client->request('GET', '/admin/petitions');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertCount(3, $crawler->filter('tbody > tr'));
        $this->assertCount(1, $crawler->filter('table button:contains("Remove petition")'));
        $this->assertCount(3, $crawler->filter('table button:contains("Ban user")'));
    }

    public function testDeletePetition()
    {
        $repository = $this->loadFixtures([
            LoadSuperuserData::class,
            LoadSpamUserPetitionData::class,
        ])->getReferenceRepository();
        $petition = $repository->getReference('user_petition_4');
        $client = $this->makeClient(true);
        $crawler = $client->request('GET', '/admin/petitions');
        $form = $crawler->selectButton('Remove petition')->form();
        $client->submit($form);
        $response = $client->getResponse();
        $this->assertEquals(302, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM user_petitions up WHERE up.id = ?', [$petition->getId()]);
        $this->assertEquals(0, $count);
    }

    public function testDeletePetitions()
    {
        $this->loadFixtures([
            LoadSuperuserData::class,
            LoadSpamUserPetitionData::class,
        ]);
        $client = $this->makeClient(true);
        $crawler = $client->request('GET', '/admin/petitions');
        $options = $crawler->filter('input[name="petition[]"]')->extract(['value']);
        $form = $crawler->selectButton('Remove petitions')->form();
        /** @var ChoiceFormField $field */
        $document = new \DOMDocument();
        foreach ($options as $option) {
            $element = $document->createElement('input', $option);
            $element->setAttribute('type', 'checkbox');
            $element->setAttribute('name', 'petition[]');
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
            SELECT COUNT(*) FROM user_petitions up
            WHERE up.id IN ('.implode(',', array_fill(1, count($options), '?')).')', $options);
        $this->assertEquals(count($options) - 1, $count);
    }

    public function testBanPetitionAuthors()
    {
        $this->loadFixtures([
            LoadSuperuserData::class,
            LoadSpamUserPetitionData::class,
        ]);
        $client = $this->makeClient(true);
        $crawler = $client->request('GET', '/admin/petitions');
        $options = $crawler->filter('input[name="petition[]"]')->extract(['value']);
        $form = $crawler->selectButton('Ban users')->form();
        /** @var ChoiceFormField $field */
        $document = new \DOMDocument();
        foreach ($options as $option) {
            $element = $document->createElement('input', $option);
            $element->setAttribute('type', 'checkbox');
            $element->setAttribute('name', 'petition[]');
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
            LEFT JOIN user_petitions up ON u.id = up.user_id
            WHERE u.enabled = 0 AND up.id IN ('.implode(',', array_fill(1, count($options), '?')).')', $options);
        $this->assertCount((int)$count, $options);
    }

    public function testBanPetitionAuthor()
    {
        $repository = $this->loadFixtures([
            LoadSuperuserData::class,
            LoadSpamUserPetitionData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('user_1');
        $client = $this->makeClient(true);
        $crawler = $client->request('GET', '/admin/petitions');
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