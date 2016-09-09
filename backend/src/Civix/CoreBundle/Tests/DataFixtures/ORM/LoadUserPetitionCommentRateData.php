<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\UserPetition\CommentRate;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadUserPetitionCommentRateData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $user1 = $this->getReference('user_1');
        $user2 = $this->getReference('user_2');
        $user4 = $this->getReference('user_4');
        $comment1 = $this->getReference('petition_comment_1');
        $comment3 = $this->getReference('petition_comment_3');

        $rate = new CommentRate();
        $rate->setComment($comment1)
            ->setRateValue(CommentRate::RATE_UP)
            ->setUser($user2);
        $manager->persist($rate);

        $rate = new CommentRate();
        $rate->setComment($comment1)
            ->setRateValue(CommentRate::RATE_DOWN)
            ->setUser($user1);
        $manager->persist($rate);

        $rate = new CommentRate();
        $rate->setComment($comment3)
            ->setRateValue(CommentRate::RATE_UP)
            ->setUser($user1);
        $manager->persist($rate);

        $rate = new CommentRate();
        $rate->setComment($comment3)
            ->setRateValue(CommentRate::RATE_DELETE)
            ->setUser($user4);
        $manager->persist($rate);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserPetitionCommentData::class];
    }
}