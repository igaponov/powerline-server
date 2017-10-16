<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Civix\CoreBundle\Entity\UserRepresentative;

class LoadUserRepresentativeRelationData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var UserRepresentative $representative */
        $representative = $this->getReference('representative_jb');
        $cicero = $this->getReference('cicero_representative_jb');
        $representative->setRepresentative($cicero);
        $manager->persist($representative);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserRepresentativeData::class, LoadRepresentativeData::class];
    }
}
