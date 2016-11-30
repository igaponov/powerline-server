<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Post\Vote;
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
        $user1 = $this->getReference('user_1');
        $user2 = $this->getReference('user_2');
        $user3 = $this->getReference('user_3');
        $user4 = $this->getReference('user_4');
        $post1 = $this->getReference('post_1');
        $post3 = $this->getReference('post_3');
        $post5 = $this->getReference('post_5');

        $answer = new Vote();
        $answer->setUser($user1);
        $answer->setPost($post1);
        $answer->setOption(Vote::OPTION_UPVOTE);
        $manager->persist($answer);

        $answer = new Vote();
        $answer->setUser($user2);
        $answer->setPost($post1);
        $answer->setOption(Vote::OPTION_DOWNVOTE);
        $manager->persist($answer);

        $answer = new Vote();
        $answer->setUser($user3);
        $answer->setPost($post1);
        $answer->setOption(Vote::OPTION_IGNORE);
        $manager->persist($answer);

        $answer = new Vote();
        $answer->setUser($user1);
        $answer->setPost($post5);
        $answer->setOption(Vote::OPTION_UPVOTE);
        $manager->persist($answer);

        $answer = new Vote();
        $answer->setUser($user2);
        $answer->setPost($post5);
        $answer->setOption(Vote::OPTION_UPVOTE);
        $manager->persist($answer);

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

        $this->addReference('post_answer_1', $answer);
        $this->addReference('post_answer_2', $answer);
        $this->addReference('post_answer_3', $answer);
        $this->addReference('post_answer_4', $answer);
        $this->addReference('post_answer_5', $answer);
    }

    public function getDependencies()
    {
        return [LoadPostData::class];
    }
}
