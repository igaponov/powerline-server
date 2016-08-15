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
        $post1 = $this->getReference('post_1');
        $post5 = $this->getReference('post_5');

        $answer1 = new Vote();
        $answer1->setUser($user1);
        $answer1->setPost($post1);
        $answer1->setOption(Vote::OPTION_UPVOTE);
        $manager->persist($answer1);

        $answer2 = new Vote();
        $answer2->setUser($user2);
        $answer2->setPost($post1);
        $answer2->setOption(Vote::OPTION_DOWNVOTE);
        $manager->persist($answer2);

        $answer3 = new Vote();
        $answer3->setUser($user3);
        $answer3->setPost($post1);
        $answer3->setOption(Vote::OPTION_IGNORE);
        $manager->persist($answer3);

        $answer4 = new Vote();
        $answer4->setUser($user1);
        $answer4->setPost($post5);
        $answer4->setOption(Vote::OPTION_UPVOTE);
        $manager->persist($answer4);

        $answer5 = new Vote();
        $answer5->setUser($user2);
        $answer5->setPost($post5);
        $answer5->setOption(Vote::OPTION_UPVOTE);
        $manager->persist($answer5);

        $manager->flush();

        $this->addReference('post_answer_1', $answer1);
        $this->addReference('post_answer_2', $answer2);
        $this->addReference('post_answer_3', $answer3);
        $this->addReference('post_answer_4', $answer4);
        $this->addReference('post_answer_5', $answer5);
    }

    public function getDependencies()
    {
        return [LoadPostData::class];
    }
}
