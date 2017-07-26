<?php
namespace Tests\Civix\CoreBundle\DataFixtures\ORM;

use Civix\CoreBundle\Entity\UserPetition\Comment;
use Civix\CoreBundle\Entity\UserPetition\CommentRate;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadUserPetitionCommentRateData extends AbstractFixture implements DependentFixtureInterface
{
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
        /** @var Comment $comment1 */
        $comment1 = $this->getReference('petition_comment_1');
        /** @var Comment $comment2 */
        $comment2 = $this->getReference('petition_comment_2');
        /** @var Comment $comment4 */
        $comment4 = $this->getReference('petition_comment_4');

        $rate = new CommentRate();
        $rate->setComment($comment1)
            ->setRateValue(CommentRate::RATE_UP)
            ->setUser($user2);
        $manager->persist($rate);

        $rate = new CommentRate();
        $rate->setComment($comment1)
            ->setRateValue(CommentRate::RATE_DOWN)
            ->setUser($user3);
        $manager->persist($rate);

        $rate = new CommentRate();
        $rate->setComment($comment2)
            ->setRateValue(CommentRate::RATE_UP)
            ->setUser($user1);
        $manager->persist($rate);

        $rate = new CommentRate();
        $rate->setComment($comment4)
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