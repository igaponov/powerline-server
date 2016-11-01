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
        $user1 = $this->getReference('user_1');
        $user2 = $this->getReference('user_2');
        $user4 = $this->getReference('user_4');

        $group1 = $this->getReference('group_1');
        $group2 = $this->getReference('group_2');

        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_OWN_POLL_ANSWERED,
            null,
            $group1,
            $user1
        );
        $manager->persist($activity);
        $this->addReference('social_activity_1', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_COMMENT_MENTIONED,
            null,
            $group2,
            $user1,
            false
        );
        $manager->persist($activity);
        $this->addReference('social_activity_2', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_COMMENT_REPLIED,
            null,
            $group1,
            $user1,
            false
        );
        $manager->persist($activity);
        $this->addReference('social_activity_3', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_FOLLOW_USER_PETITION_COMMENTED,
            null,
            $group2
        );
        $manager->persist($activity);
        $this->addReference('social_activity_4', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_FOLLOW_POLL_COMMENTED,
            null,
            $group1
        );
        $manager->persist($activity);
        $this->addReference('social_activity_5', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_GROUP_PERMISSIONS_CHANGED,
            null,
            $group1,
            $user1,
            false
        );
        $manager->persist($activity);
        $this->addReference('social_activity_6', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_FOLLOW_POST_CREATED,
            $user4,
            $group2
        );
        $manager->persist($activity);
        $this->addReference('social_activity_7', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_OWN_POST_COMMENTED,
            null,
            $group1,
            $user1
        );
        $manager->persist($activity);
        $this->addReference('social_activity_8', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_OWN_POST_VOTED,
            null,
            $group2,
            $user1,
            false
        );
        $manager->persist($activity);
        $this->addReference('social_activity_9', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_FOLLOW_REQUEST,
            null,
            null,
            $user1
        );
        $manager->persist($activity);
        $this->addReference('social_activity_10', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_JOIN_TO_GROUP_APPROVED,
            null,
            $group2,
            $user1
        );
        $manager->persist($activity);
        $this->addReference('social_activity_11', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_OWN_USER_PETITION_SIGNED,
            null,
            $group1,
            $user1
        );
        $manager->persist($activity);
        $this->addReference('social_activity_12', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_OWN_USER_PETITION_COMMENTED,
            null,
            $group2,
            $user1
        );
        $manager->persist($activity);
        $this->addReference('social_activity_13', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_OWN_POLL_COMMENTED,
            null,
            $group2,
            $user1
        );
        $manager->persist($activity);
        $this->addReference('social_activity_14', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_FOLLOW_POST_COMMENTED,
            null,
            $group1
        );
        $manager->persist($activity);
        $this->addReference('social_activity_15', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_FOLLOW_USER_PETITION_CREATED,
            $user2,
            $group2
        );
        $manager->persist($activity);
        $this->addReference('social_activity_16', $activity);
        $activity = $this->generateSocialActivity(
            SocialActivity::TYPE_POST_MENTIONED,
            null,
            $group2,
            $user1
        );
        $manager->persist($activity);
        $this->addReference('social_activity_17', $activity);

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
        return [LoadGroupData::class];
    }
}
