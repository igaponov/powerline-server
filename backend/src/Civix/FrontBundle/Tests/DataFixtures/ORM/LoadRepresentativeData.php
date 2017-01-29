<?php

namespace Civix\FrontBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\State;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadRepresentativeData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = $this->getReference('user_1');
        /** @var State $state */
        $state = $this->getReference('state_wa');

        $representative = new Representative($user);
        $representative->setOfficialTitle('Vice President');
        $representative->setCity('Los Angeles');
        $representative->setAddress('6153 Smokey Ln');
        $representative->setPhone('(672)-586-7816');
        $representative->setPrivatePhone('+1-(672)-586-7816');
        $representative->setEmail('josephb26@example.com');
        $representative->setPrivateEmail('josephb260@secret.com');
        $representative->setIsNonLegislative(true);
        $representative->setState($state);
        $manager->persist($representative);
        $manager->flush();
        $this->addReference('representative_1', $representative);
    }

    public function getDependencies()
    {
        return [LoadUserData::class, LoadStateData::class];
    }
}