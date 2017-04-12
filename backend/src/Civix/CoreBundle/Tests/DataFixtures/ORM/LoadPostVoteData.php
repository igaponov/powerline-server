<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\Post\Vote;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadPostVoteData extends AbstractFixture implements DependentFixtureInterface
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
        /** @var Post $post1 */
        $post1 = $this->getReference('post_1');
        /** @var Post $post3 */
        $post3 = $this->getReference('post_3');
        /** @var Post $post5 */
        $post5 = $this->getReference('post_5');

        $answer = new Vote();
        $answer->setUser($user1);
        $answer->setPost($post1);
        $answer->setOption(Vote::OPTION_UPVOTE);
        $manager->persist($answer);
        $this->addReference('post_answer_1', $answer);

        $answer = new Vote();
        $answer->setUser($user2);
        $answer->setPost($post1);
        $answer->setOption(Vote::OPTION_DOWNVOTE);
        $manager->persist($answer);
        $this->addReference('post_answer_2', $answer);

        $answer = new Vote();
        $answer->setUser($user3);
        $answer->setPost($post1);
        $answer->setOption(Vote::OPTION_IGNORE);
        $manager->persist($answer);
        $this->addReference('post_answer_3', $answer);

        $answer = new Vote();
        $answer->setUser($user1);
        $answer->setPost($post5);
        $answer->setOption(Vote::OPTION_UPVOTE);
        $manager->persist($answer);
        $this->addReference('post_answer_4', $answer);

        $answer = new Vote();
        $answer->setUser($user2);
        $answer->setPost($post5);
        $answer->setOption(Vote::OPTION_UPVOTE);
        $manager->persist($answer);
        $this->addReference('post_answer_5', $answer);

        $answer = new Vote();
        $answer->setUser($user3);
        $answer->setPost($post3);
        $answer->setOption(Vote::OPTION_UPVOTE);
        $manager->persist($answer);

        $answer = new Vote();
        $answer->setUser($user4);
        $answer->setPost($post3);
        $answer->setOption(Vote::OPTION_UPVOTE);
        $manager->persist($answer);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadPostData::class];
    }
}
