<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Group;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadTagData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Group $group1 */
        $group1 = $this->getReference('group_1');
        /** @var Group $group2 */
        $group2 = $this->getReference('group_2');

        $tag = new Group\Tag('test tag');
        $this->addReference('group_tag_1', $tag);
        $group1->addTag($tag);
        $group2->addTag($tag);
        $tag = new Group\Tag('tag #2');
        $this->addReference('group_tag_2', $tag);
        $group1->addTag($tag);
        $tag = new Group\Tag('another test tag');
        $this->addReference('group_tag_3', $tag);
        $group1->addTag($tag);
        $tag = new Group\Tag('testing');
        $this->addReference('group_tag_4', $tag);
        $group1->addTag($tag);
        $tag = new Group\Tag('tesco');
        $this->addReference('group_tag_5', $tag);
        $group1->addTag($tag);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [LoadGroupData::class];
    }
}