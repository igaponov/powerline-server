<?php

namespace Civix\FrontBundle\Tests\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserRepresentative;
use Civix\FrontBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\FrontBundle\Tests\DataFixtures\ORM\LoadUserRepresentativeData;
use Civix\FrontBundle\Tests\DataFixtures\ORM\LoadSuperuserData;
use Doctrine\DBAL\Connection;

class LocalGroupControllerTest extends WebTestCase
{
    public function testLocalGroupPage()
    {
        $repository = $this->loadFixtures([
            LoadSuperuserData::class,
            LoadGroupData::class,
        ])->getReferenceRepository();
        /** @var Group $groupUs */
        $groupUs = $repository->getReference('group_us');
        /** @var Group $groupCa */
        $groupCa = $repository->getReference('group_ca');
        /** @var Group $groupLa */
        $groupLa = $repository->getReference('group_la');
        $client = $this->makeClient(true);
        $crawler = $client->request('GET', '/admin/local-groups');
        $this->assertCount(1, $crawler->filter('.dropdown-toggle:contains("Select the country group")'));
        $link = $crawler->selectLink($groupUs->getOfficialName())->link();
        $crawler = $client->click($link);
        $this->assertCount(1, $crawler->filter('.dropdown-toggle:contains("Select the state group")'));
        $this->assertCount(1, $crawler->filter('#state-groups tbody tr td:contains("Table is empty.")'));
        $link = $crawler->selectLink($groupCa->getOfficialName())->link();
        $crawler = $client->click($link);
        $elements = $crawler->filter('#state-groups tbody tr');
        $this->assertCount(1, $elements);
        $this->assertEquals($groupLa->getOfficialName(), $elements->getNode(0)->getAttribute('data-filter-item'));
    }

    public function testLocalGroupAssign()
    {
        $repository = $this->loadFixtures([
            LoadSuperuserData::class,
            LoadGroupData::class,
            LoadUserRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var Group $groupCa */
        $groupCa = $repository->getReference('group_ca');
        /** @var Group $groupLa */
        $groupLa = $repository->getReference('group_la');
        /** @var UserRepresentative $representative */
        $representative = $repository->getReference('representative_1');
        $client = $this->makeClient(true);
        $crawler = $client->request('GET', '/admin/local-groups?id='.$groupCa->getId());
        $link = $crawler->selectLink('Assign')->link();
        $crawler = $client->click($link);
        $form = $crawler->selectButton('Submit')->form();
        $form['local_representative[localRepresentatives]']->setValue([1]);
        $client->submit($form);
        $crawler = $client->followRedirect();
        $this->assertCount(1, $crawler->filter('.alert-info:contains("Assign to local group is completed")'));
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM user_representatives WHERE local_group = ? AND id = ?', [$groupLa->getId(), $representative->getId()]);
        $this->assertEquals(1, $count);
    }
}