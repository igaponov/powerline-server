<?php

namespace Civix\FrontBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Superuser;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadSuperuserData extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        $user = new Superuser();
        $user->setUsername('admin')
            ->setPlainPassword('admin')
            ->setEmail('admin@mail.com');
        $manager->persist($user);
        $manager->flush();
        $this->addReference('superuser_1', $user);
    }
}