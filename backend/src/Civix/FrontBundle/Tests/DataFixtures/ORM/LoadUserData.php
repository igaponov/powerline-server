<?php

namespace Civix\FrontBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use libphonenumber\PhoneNumber;

class LoadUserData extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setUsername('user1')
            ->setFirstName('User')
            ->setLastName('One')
            ->setEmail('user1@example.com')
            ->setPlainPassword('user1')
            ->setBirth(new \DateTime('-30 years'))
            ->setPhone((new PhoneNumber())->setCountryCode(1)->setNationalNumber('234567890'));
        $manager->persist($user);
        $manager->flush();
        $this->addReference('user_1', $user);
    }
}