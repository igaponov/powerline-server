<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Karma;
use Civix\CoreBundle\Entity\Post\Vote;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * LoadUserData.
 */
class LoadPostVoteKarmaData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Vote $vote */
        $vote = $this->getReference('post_answer_2');

        $karma = new Karma($vote->getPost()->getUser(), Karma::TYPE_RECEIVE_UPVOTE_ON_POST, 2, [
            'post_id' => $vote->getPost()->getId(),
            'vote_id' => $vote->getId(),
        ]);
        $manager->persist($karma);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadPostVoteData::class];
    }
}
