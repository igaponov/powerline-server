<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\SocialActivity;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * LoadUserData.
 */
class LoadSocialActivityData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $user1 = $this->getReference('followertest');
        $user2 = $this->getReference('userfollowtest1');
        $user3 = $this->getReference('userfollowtest2');
        $user4 = $this->getReference('userfollowtest3');
        $group1 = $this->getReference('testfollowsecretgroups');
        $group2 = $this->getReference('testfollowprivategroups');
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_OWN_POLL_ANSWERED,
            $user2,
            $group1,
            $user1
        );
        $manager->persist($activity);
        $this->addReference('social_activity_1', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_COMMENT_MENTIONED,
            $user2,
            $group2,
            $user1,
            false
        );
        $manager->persist($activity);
        $this->addReference('social_activity_2', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_COMMENT_REPLIED,
            $user3,
            $group1,
            $user1,
            false
        );
        $manager->persist($activity);
        $this->addReference('social_activity_3', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_FOLLOW_USER_PETITION_COMMENTED,
            $user4,
            $group2,
            $user1
        );
        $manager->persist($activity);
        $this->addReference('social_activity_4', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_FOLLOW_POLL_COMMENTED,
            $user1,
            $group1,
            $user2
        );
        $manager->persist($activity);
        $this->addReference('social_activity_5', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_GROUP_PERMISSIONS_CHANGED,
            $user4,
            $group1,
            null,
            false
        );
        $manager->persist($activity);
        $this->addReference('social_activity_6', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_GROUP_POST_CREATED,
            $user3,
            $group2
        );
        $manager->persist($activity);
        $this->addReference('social_activity_7', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_OWN_POST_COMMENTED,
            $user2
        );
        $manager->persist($activity);
        $this->addReference('social_activity_8', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_OWN_POST_VOTED,
            $user3,
            $group2,
            $user1,
            false
        );
        $manager->persist($activity);
        $this->addReference('social_activity_9', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_FOLLOW_REQUEST,
            $user4
        );
        $manager->persist($activity);
        $this->addReference('social_activity_10', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_JOIN_TO_GROUP_APPROVED,
            $user1,
            $group2,
            $user2
        );
        $manager->persist($activity);
        $this->addReference('social_activity_11', $activity);
        $manager->flush();
    }

    private function generateSocialActivity(
        $type,
        $following = null,
        $group = null,
        $recipient = null,
        $ignore = true
    ) {
        $activity = new SocialActivity($type, $following, $group);
        if ($recipient) {
            $activity->setRecipient($recipient);
        }
        $activity->setIgnore($ignore);

        return $activity;
    }

    public function getDependencies()
    {
        return [LoadUserData::class, LoadGroupFollowerTestData::class];
    }
}
