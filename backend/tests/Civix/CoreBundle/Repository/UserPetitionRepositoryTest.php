<?php

namespace Tests\Civix\CoreBundle\Repository;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionSignatureData;

class UserPetitionRepositoryTest extends WebTestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::bootFixtureLoader();
        self::$fixtureLoader->loadFixtures([
            LoadUserPetitionSignatureData::class,
        ]);
    }

    public function testFindPetitionWithUserSignature()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $petition = $repository->getReference('user_petition_1');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $result = $em->getRepository(UserPetition::class)->findPetitionWithUserSignature($petition->getId(), $user);
        $this->assertInstanceOf(UserPetition::class, $result);
        $this->assertCount(1, $result->getSignatures());
    }

    public function testFindPetitionWithEmptyUserSignature()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_4');
        $petition = $repository->getReference('user_petition_1');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $result = $em->getRepository(UserPetition::class)->findPetitionWithUserSignature($petition->getId(), $user);
        $this->assertInstanceOf(UserPetition::class, $result);
        $this->assertCount(0, $result->getSignatures());
    }

    public function testFindPetitionWithUserSignatureReturnsNull()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $result = $em->getRepository(UserPetition::class)->findPetitionWithUserSignature(0, $user);
        $this->assertNull($result);
    }
}
