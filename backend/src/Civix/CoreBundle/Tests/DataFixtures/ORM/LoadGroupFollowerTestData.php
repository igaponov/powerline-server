<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Civix\CoreBundle\Entity\Group;

/**
 * LoadGroupData.
 */
class LoadGroupFollowerTestData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    const GROUP_NAME = 'testgroup';
    const GROUP_PASSWORD = 'testgroup7ZAPe3QnZhbdec';
    const GROUP_EMAIL = 'testgroup@example.com';

    /**
     * @var ContainerInterface
     */
    private $container;

    /** @var ObjectManager */
    private $manager;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        $this->addReference(
            'group', 
            $this->createGroup(
                self::GROUP_NAME,
                null,
                $this->getReference('user_1')
            )
        );
        $this->addReference(
            'testfollowsecretgroups',
            $this->createGroup(
                'testfollowsecretgroups',
                Group::GROUP_TRANSPARENCY_SECRET,
                $this->getReference('userfollowtest1')
            )
        );
        $this->addReference(
            'testfollowprivategroups',
            $this->createGroup(
                'testfollowprivategroups',
                Group::GROUP_TRANSPARENCY_PRIVATE,
                $this->getReference('user_2')
            )
        );
    }

    /**
     * @param $groupName
     * @param null $transparency
     * @param User $owner
     * @return Group Group
     * @internal param null $password
     */
    private function createGroup($groupName, $transparency = null, User $owner = null)
    {
        $faker = Factory::create();
        $transparency = $transparency ?: Group::GROUP_TRANSPARENCY_PUBLIC;

        $group = new Group();
        $group->setAcronym($groupName)
            ->setGroupType(Group::GROUP_TYPE_COMMON)
            ->setOfficialName($groupName)
            ->setManagerEmail("$groupName@example.com")
            ->setManagerFirstName($groupName)
            ->setManagerLastName($groupName)
            ->setTransparency($transparency)
            ->setPetitionPerMonth($groupName == self::GROUP_NAME ? 4 : 5)
            ->setPetitionPercent(45)
            ->setPetitionDuration(25)
            ->setCreatedAt($faker->dateTimeBetween('-1 day', '-5 seconds'));

        if ($groupName == self::GROUP_NAME) {
            $group
                ->setMembershipControl(Group::GROUP_MEMBERSHIP_PASSCODE)
                ->setMembershipPasscode('secret_passcode');
        }

        if ($owner) {
            $group->setOwner($owner);
        }

        $this->manager->persist($group);
        $this->manager->flush();

        return $group;
    }

    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}
