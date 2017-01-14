<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadSpamPostData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var User $user1 */
        $user1 = $this->getReference('user_1');
        /** @var User $user2 */
        $user2 = $this->getReference('user_2');
        /** @var User $user3 */
        $user3 = $this->getReference('user_3');
        /** @var User $user4 */
        $user4 = $this->getReference('user_4');

        /** @var Post $post */
        $post = $this->getReference('post_1');
        $post->markAsSpam($user3);
        $manager->persist($post);

        $post = $this->getReference('post_3');
        $post->markAsSpam($user2);
        $manager->persist($post);

        $post = $this->getReference('post_4');
        $post->markAsSpam($user1);
        $post->markAsSpam($user2);
        $post->markAsSpam($user3);
        $post->markAsSpam($user4);
        $manager->persist($post);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadPostData::class];
    }
}
