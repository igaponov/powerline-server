<?php

namespace Civix\FrontBundle\Tests\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Service\Representative\RepresentativeManager;
use Civix\FrontBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\FrontBundle\Tests\DataFixtures\ORM\LoadRepresentativeData;
use Civix\FrontBundle\Tests\DataFixtures\ORM\LoadSuperuserData;
use Doctrine\DBAL\Connection;

class RepresentativeControllerTest extends WebTestCase
{
    public function testIndexPage()
    {
        $this->loadFixtures([
            LoadSuperuserData::class,
            LoadRepresentativeData::class,
        ]);
        $crawler = $this->fetchCrawler('/admin/representatives', 'GET', true);
        $this->assertCount(1, $crawler->filter('tbody > tr'));
        $this->assertCount(1, $crawler->filter('a:contains("Edit")'));
        $this->assertCount(1, $crawler->filter('button:contains("Remove")'));
    }

    public function testEditRepresentative()
    {
        $this->loadFixtures([
            LoadSuperuserData::class,
            LoadRepresentativeData::class,
            LoadGroupData::class,
        ]);
        $client = $this->makeClient(true);
        $crawler = $client->request('GET', '/admin/representatives');
        $link = $crawler->selectLink('Edit')->link();
        $crawler = $client->click($link);
        $form = $crawler->selectButton('Submit')->form();
        $form['representative[officialTitle]'] = 'Senator';
        $form['representative[phone]'] = '+1 (202) 224-4451';
        $form['representative[privatePhone]'] = '+1 (020) 442-5144';
        $form['representative[city]'] = 'New York';
        $form['representative[state]'] = 'CA';
        $form['representative[country]'] = 'USA';
        $form['representative[email]'] = 'new-test@email.com';
        $form['representative[privateEmail]'] = 'private@email.com';
        $form['representative[localGroup]'] = 2;
        $client->submit($form);
        $crawler = $client->followRedirect();
        $this->assertContains(
            'Representative was saved',
            $crawler->filter('.alert-info')->text()
        );
        $submittedForm = $crawler->selectButton('Submit')->form();
        foreach ($form->all() as $field) {
            $this->assertEquals($field->getValue(), $submittedForm->get($field->getName())->getValue());
        }
    }

    public function testRemoveRepresentative()
    {
        $this->loadFixtures([
            LoadSuperuserData::class,
            LoadRepresentativeData::class,
            LoadGroupData::class,
        ]);
        $client = $this->makeClient(true);
        $crawler = $client->request('GET', '/admin/representatives');
        $form = $crawler->selectButton('Remove')->form();
        $client->submit($form);
        $response = $client->getResponse();
        $this->assertEquals(302, $response->getStatusCode(), $response->getContent());
        $crawler = $client->followRedirect();
        $this->assertCount(1, $crawler->filter('tbody > tr:contains("Table is empty.")'));
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM representatives');
        $this->assertEquals(0, $count);
    }

    public function testApproveRepresentative()
    {
        $repository = $this->loadFixtures([
            LoadSuperuserData::class,
            LoadRepresentativeData::class,
        ])->getReferenceRepository();
        $representative = $repository->getReference('representative_1');
        $service = $this->getMockBuilder(RepresentativeManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['synchronizeRepresentative'])
            ->getMock();
        $service->expects($this->once())
            ->method('synchronizeRepresentative');
        $client = $this->makeClient(true);
        $crawler = $client->request('GET', '/admin/representatives/approvals');
        $form = $crawler->selectButton('Approve')->form();
        $client->disableReboot();
        $client->getContainer()->set('civix_core.representative_manager', $service);
        $client->submit($form);
        $response = $client->getResponse();
        $this->assertEquals(302, $response->getStatusCode(), $response->getContent());
        $crawler = $client->followRedirect();
        $this->assertCount(1, $crawler->filter('tbody > tr:contains("Table is empty.")'));
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM representatives WHERE status = ? AND id = ?', [Representative::STATUS_ACTIVE, $representative->getId()]);
        $this->assertEquals(1, $count);
    }
}