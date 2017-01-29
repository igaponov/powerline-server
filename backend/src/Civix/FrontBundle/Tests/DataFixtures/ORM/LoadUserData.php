<?php

namespace Civix\FrontBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadUserData extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setUsername('user1')
            ->setFirstName('User')
            ->setLastName('One')
            ->setEmail("user1@example.com")
            ->setPlainPassword('user1')
            ->setBirth(new \DateTime('-30 years'))
            ->setPhone('+1234567890')
            ->setSalt(base_convert(sha1(uniqid(mt_rand(), true)), 16, 36));
        $manager->persist($user);
        $manager->flush();
        $this->addReference('user_1', $user);
    }
}