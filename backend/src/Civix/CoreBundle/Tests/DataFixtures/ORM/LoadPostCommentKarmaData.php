<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\BaseCommentRate;
use Civix\CoreBundle\Entity\Karma;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * LoadUserData.
 */
class LoadPostCommentKarmaData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var BaseCommentRate $rate */
        $rate = $this->getReference('post_comment_3_rate_1');

        $karma = new Karma($rate->getComment()->getUser(), Karma::TYPE_RECEIVE_UPVOTE_ON_COMMENT, 2, [
            'type' => $rate->getComment()->getEntityType(),
            'comment_id' => $rate->getComment()->getId(),
            'rate_id' => $rate->getId(),
        ]);
        $manager->persist($karma);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadPostCommentRateData::class];
    }
}
