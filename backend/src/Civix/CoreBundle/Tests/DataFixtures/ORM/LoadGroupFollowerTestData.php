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
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

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
            $this->createGroup(self::GROUP_NAME, self::GROUP_PASSWORD)
        );
        $this->addReference(
            'testfollowsecretgroups',
            $this->createGroup(
                'testfollowsecretgroups', 
                null, 
                Group::GROUP_TRANSPARENCY_SECRET,
                $this->getReference('userfollowtest1')
            )
        );
        $this->addReference(
            'testfollowprivategroups',
            $this->createGroup('testfollowprivategroups', null, Group::GROUP_TRANSPARENCY_PRIVATE)
        );
    }

    /**
     * @param $groupName
     * @param null $password
     * @param null $transparency
     * @param User $owner
     * @return Group Group
     */
    private function createGroup($groupName, $password = null, $transparency = null, User $owner = null)
    {
        $faker = Factory::create();
        $password = $password ?: $groupName;
        $transparency = $transparency ?: Group::GROUP_TRANSPARENCY_PUBLIC;

        $group = new Group();
        $group->setAcronym($groupName)
            ->setGroupType(Group::GROUP_TYPE_COMMON)
            ->setManagerEmail("$groupName@example.com")
            ->setManagerFirstName($groupName)
            ->setManagerLastName($groupName)
            ->setPassword($password)
            ->setTransparency($transparency)
            ->setUsername($groupName)
            ->setToken($groupName == self::GROUP_NAME ? 'secret_token' : $groupName)
            ->setPetitionPerMonth($groupName == self::GROUP_NAME ? 4 : 5)
            ->setPetitionPercent(45)
            ->setPetitionDuration(25)
            ->setMembershipControl(Group::GROUP_MEMBERSHIP_PASSCODE)
            ->setMembershipPasscode('secret_passcode')
            ->setCreatedAt($faker->dateTimeBetween('-1 day', '-5 seconds'));
        
        if ($owner) {
            $group->setOwner($owner);
        }

        /** @var PasswordEncoderInterface $encoder */
        $encoder = $this->container->get('security.encoder_factory')->getEncoder($group);
        $encodedPassword = $encoder->encodePassword($password, $group->getSalt());
        $group->setPassword($encodedPassword);

        $this->manager->persist($group);
        $this->manager->flush();

        return $group;
    }

    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}
