<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserRepresentative;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadGroupUserRepresentativesData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Group $group */
        $group = $this->getReference('group_1');
        /** @var UserRepresentative $representative */
        $representative = $this->getReference('representative_wc');
        $representative->setLocalGroup($group);

        $manager->persist($representative);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupData::class, LoadUserRepresentativeData::class];
    }
}