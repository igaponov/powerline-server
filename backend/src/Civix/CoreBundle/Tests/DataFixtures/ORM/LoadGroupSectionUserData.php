<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\GroupSection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadGroupSectionUserData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $user1 = $this->getReference('user_1');
        $user2 = $this->getReference('user_2');
        $user3 = $this->getReference('user_3');
        $user4 = $this->getReference('user_4');
        /** @var GroupSection $section1 */
        $section1 = $this->getReference('group_1_section_1');
        /** @var GroupSection $section3 */
        $section3 = $this->getReference('group_3_section_1');

        $section1->addUser($user2)
            ->addUser($user3)
            ->addUser($user4);
        $manager->persist($section1);

        $section3->addUser($user1);
        $manager->persist($section3);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupSectionData::class];
    }
}