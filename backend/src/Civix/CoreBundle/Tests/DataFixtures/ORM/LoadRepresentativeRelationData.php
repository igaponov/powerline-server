<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Civix\CoreBundle\Entity\Representative;

class LoadRepresentativeRelationData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Representative $representative */
        $representative = $this->getReference('representative_jb');
        $cicero = $this->getReference('cicero_representative_jb');
        $representative->setCiceroRepresentative($cicero);
        $manager->persist($representative);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadRepresentativeData::class, LoadCiceroRepresentativeData::class];
    }
}
