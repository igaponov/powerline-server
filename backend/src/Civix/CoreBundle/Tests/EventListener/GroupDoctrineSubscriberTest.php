<?php
namespace Civix\CoreBundle\Tests\EventListener;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Group;
use Doctrine\ORM\EntityManager;

class GroupDoctrineSubscriberTest extends WebTestCase
{
    public function testNoDuplicateSlug()
    {
        $this->loadFixtures([]);
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $group = new Group();
        $group->setOfficialName('Test Group Name')
            ->setUsername('group1')
            ->setPassword(sha1(123));
        $em->persist($group);
        $em->flush();
        $em->refresh($group);
        $this->assertEquals('test-group-name', $group->getSlug());
    }

    public function testOneDuplicateSlug()
    {
        $this->loadFixtures([]);
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->createGroup('test-name');
        $em->flush();
        $group = new Group();
        $group->setOfficialName('Test Name')
            ->setUsername('group1')
            ->setPassword(sha1(123));
        $em->persist($group);
        $em->flush();
        $em->refresh($group);
        $this->assertEquals('test-name-1', $group->getSlug());
    }

    public function testOneLetterDuplicateSlug()
    {
        $this->loadFixtures([]);
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->createGroup('test-name');
        $this->createGroup('test-name-x');
        $em->flush();
        $group = new Group();
        $group->setOfficialName('Test Name')
            ->setUsername('group1')
            ->setPassword(sha1(123));
        $em->persist($group);
        $em->flush();
        $em->refresh($group);
        $this->assertEquals('test-name-1', $group->getSlug());
    }

    public function testLetterDuplicateSlug()
    {
        $this->loadFixtures([]);
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->createGroup('test-name-q');
        $em->flush();
        $group = new Group();
        $group->setOfficialName('Test Name')
            ->setUsername('group1')
            ->setPassword(sha1(123));
        $em->persist($group);
        $em->flush();
        $em->refresh($group);
        $this->assertEquals('test-name', $group->getSlug());
    }

    public function testManyDuplicateSlug()
    {
        $this->loadFixtures([]);
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->createGroup('test-name');
        $this->createGroup('test-name-1');
        $this->createGroup('test-name-5');
        $em->flush();
        $group = new Group();
        $group->setOfficialName('Test Name')
            ->setUsername('group1')
            ->setPassword(sha1(123));
        $em->persist($group);
        $em->flush();
        $em->refresh($group);
        $this->assertEquals('test-name-6', $group->getSlug());
    }

    public function testManyDuplicateSlugWithLetter()
    {
        $this->loadFixtures([]);
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->createGroup('test-name');
        $this->createGroup('test-name-1');
        $this->createGroup('test-name-5');
        $this->createGroup('test-name-a');
        $em->flush();
        $group = new Group();
        $group->setOfficialName('Test Name')
            ->setUsername('group1')
            ->setPassword(sha1(123));
        $em->persist($group);
        $em->flush();
        $em->refresh($group);
        $this->assertEquals('test-name-6', $group->getSlug());
    }

    private function createGroup($slug)
    {
        $group = new Group();
        $group->setOfficialName(uniqid('name-', true))
            ->setUsername(uniqid('username-',true))
            ->setPassword(sha1(123))
            ->setSlug($slug);
        $this->getContainer()->get('doctrine.orm.entity_manager')->persist($group);
    }
}