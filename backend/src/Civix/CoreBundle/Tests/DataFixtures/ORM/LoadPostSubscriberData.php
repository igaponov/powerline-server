<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * LoadUserData.
 */
class LoadPostSubscriberData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Post $post */
        $post = $this->getReference('post_1');
        /** @var User $user */
        $user = $this->getReference('user_1');
        $user->addPostSubscription($post);
        $manager->persist($post);

        /** @var Post $post */
        $post = $this->getReference('post_5');

        /** @var User $user */
        $user = $this->getReference('user_2');
        $user->addPostSubscription($post);

        /** @var User $user */
        $user = $this->getReference('user_3');
        $user->addPostSubscription($post);
        $manager->persist($post);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadPostData::class];
    }
}