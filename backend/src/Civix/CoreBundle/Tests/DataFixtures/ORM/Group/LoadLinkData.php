<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Group;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class LoadLinkData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        /** @var Group $group1 */
        $group1 = $this->getReference('group_1');

        $link = new Group\Link($faker->url, $faker->sentence);
        $this->addReference('group_link_1', $link);
        $group1->addLink($link);
        $group1->addLink(new Group\Link($faker->url, $faker->sentence));
        $group1->addLink(new Group\Link($faker->url, $faker->sentence));

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [LoadGroupData::class];
    }
}