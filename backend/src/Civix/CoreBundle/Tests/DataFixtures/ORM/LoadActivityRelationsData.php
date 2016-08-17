<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserPetition;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * LoadUserData.
 */
class LoadActivityRelationsData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Activity $userPetitionActivity */
        $userPetitionActivity = $this->getReference('activity_user_petition');
        /** @var UserPetition $userPetition */
        $userPetition = $this->getReference('user_petition_1');
        $userPetitionActivity->setPetition($userPetition);
        $manager->persist($userPetitionActivity);

        /** @var Activity $postActivity */
        $postActivity = $this->getReference('activity_post');
        /** @var Post $post */
        $post = $this->getReference('post_1');
        $postActivity->setPost($post);
        $manager->persist($postActivity);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadActivityData::class, LoadUserPetitionData::class, LoadPostData::class];
    }
}