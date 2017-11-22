<?php

namespace Tests\Civix\CoreBundle\DataFixtures\ORM;

use Civix\CoreBundle\Entity\RecoveryToken;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadRecoveryTokenData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user1 */
        $user1 = $this->getReference('user_1');
        /** @var User $user2 */
        $user2 = $this->getReference('user_2');

        $token = new RecoveryToken($user1, 'device1');
        $manager->persist($token);
        $this->setReference('recovery_token_1', $token);

        $token = new RecoveryToken($user2, 'device2');
        $token->confirm();
        $manager->persist($token);
        $this->setReference('recovery_token_2', $token);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}