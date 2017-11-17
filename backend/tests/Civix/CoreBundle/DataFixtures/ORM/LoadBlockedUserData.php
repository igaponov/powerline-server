<?php

namespace Tests\Civix\CoreBundle\DataFixtures\ORM;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadBlockedUserData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user1 */
        $user1 = $this->getReference('user_1');
        /** @var User $user2 */
        $user2 = $this->getReference('user_2');
        /** @var User $user4 */
        $user4 = $this->getReference('user_4');

        $user2->blockBy($user1);
        $user4->blockBy($user1);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}