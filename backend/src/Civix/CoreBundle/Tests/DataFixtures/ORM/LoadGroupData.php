<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Civix\CoreBundle\Entity\Group;

class LoadGroupData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        /** @var User $user1 */
        $user1 = $this->getReference('user_1');
        /** @var User $user2 */
        $user2 = $this->getReference('user_2');
        /** @var User $user3 */
        $user3 = $this->getReference('user_3');

        // public
        $group = new Group();
        $group->setAcronym('group1')
            ->setGroupType(Group::GROUP_TYPE_COMMON)
            ->setManagerEmail('group1@example.com')
            ->setManagerFirstName('John')
            ->setManagerLastName('Doe')
            ->setTransparency(Group::GROUP_TRANSPARENCY_PUBLIC)
            ->setOfficialName('group1')
            ->setPetitionPerMonth(5)
            ->setPetitionPercent(45)
            ->setPetitionDuration(25)
            ->setMembershipControl(Group::GROUP_MEMBERSHIP_PUBLIC)
            ->setCreatedAt($faker->dateTimeBetween('-1 day', '-10 minutes'))
            ->setOwner($user1)
            ->setAvatarFileName(uniqid('', true).'.jpg')
            ->setRequiredPermissions([
                'permissions_name',
                'permissions_address',
                'permissions_city',
                'permissions_state',
                'permissions_country',
                'permissions_zip_code',
                'permissions_email',
                'permissions_phone',
                'permissions_responses',
            ]);
        $manager->persist($group);
        $this->addReference('group_1', $group);

        // private
        $group = new Group();
        $group->setAcronym('group2')
            ->setGroupType(Group::GROUP_TYPE_COMMON)
            ->setManagerEmail('group2@example.com')
            ->setManagerFirstName('Alan')
            ->setManagerLastName('Johnson')
            ->setTransparency(Group::GROUP_TRANSPARENCY_PRIVATE)
            ->setOfficialName('group2')
            ->setPetitionPerMonth(3)
            ->setPetitionPercent(35)
            ->setPetitionDuration(15)
            ->setMembershipControl(Group::GROUP_MEMBERSHIP_APPROVAL)
            ->setCreatedAt($faker->dateTimeBetween('-5 day', '-10 hours'))
            ->setAvatarFileName('58d5e28b2a8f3.jpeg')
            ->setOwner($user2)
            ->getBanner()
            ->setName('b2a8f358d5e28.png');
        $manager->persist($group);
        $this->addReference('group_2', $group);

        // secret
        $group = new Group();
        $group->setAcronym('group3')
            ->setGroupType(Group::GROUP_TYPE_COMMON)
            ->setManagerEmail('group3@example.com')
            ->setManagerFirstName('Quentin')
            ->setManagerLastName('Ward')
            ->setTransparency(Group::GROUP_TRANSPARENCY_SECRET)
            ->setOfficialName('group3')
            ->setPetitionPerMonth(10)
            ->setPetitionPercent(55)
            ->setPetitionDuration(35)
            ->setMembershipControl(Group::GROUP_MEMBERSHIP_PASSCODE)
            ->setMembershipPasscode('secret_passcode')
            ->setCreatedAt($faker->dateTimeBetween('-1 week', '-1 day'))
            ->setOwner($user3);
        $manager->persist($group);
        $this->addReference('group_3', $group);

        // super secret
        $group = new Group();
        $group->setAcronym('group4')
            ->setGroupType(Group::GROUP_TYPE_COMMON)
            ->setManagerEmail('group4@example.com')
            ->setManagerFirstName('Tom')
            ->setManagerLastName('Carter')
            ->setTransparency(Group::GROUP_TRANSPARENCY_TOP_SECRET)
            ->setOfficialName('group4')
            ->setPetitionPerMonth(15)
            ->setPetitionPercent(75)
            ->setPetitionDuration(55)
            ->setMembershipControl(Group::GROUP_MEMBERSHIP_PASSCODE)
            ->setMembershipPasscode('top_secret_passcode')
            ->setCreatedAt($faker->dateTimeBetween('-3 weeks', '-1 week'))
            ->setOwner($user1);
        $manager->persist($group);
        $this->addReference('group_4', $group);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [LoadUserData::class];
    }
}