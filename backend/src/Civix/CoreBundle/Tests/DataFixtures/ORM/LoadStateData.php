<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\State;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadStateData extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        $state = new State();
        $state->setCode('CA')
            ->setName('California');
        $manager->persist($state);
        $this->addReference('state_ca', $state);
        
        $state = new State();
        $state->setCode('WA')
            ->setName('Washington');
        $manager->persist($state);
        $this->addReference('state_wa', $state);
        
        $state = new State();
        $state->setCode('DC')
            ->setName('District of Columbia');
        $manager->persist($state);
        $this->addReference('state_dc', $state);
        
        $manager->flush();
    }
}
