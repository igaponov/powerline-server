<?php

namespace Tests\Civix\CoreBundle\DataFixtures\ORM;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Poll\Comment;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupQuestionData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadPollCommentData extends AbstractFixture implements DependentFixtureInterface
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
        /** @var Question $poll1 */
        $poll1 = $this->getReference('group_question_1');
        /** @var Question $poll5 */
        $poll5 = $this->getReference('group_question_5');

        $comment1 = (new Comment($user1))
            ->setQuestion($poll1)
            ->setCommentBody('Test for comment body 1')
            ->setCommentBodyHtml('<div>Comment Body HTML 1</div>');
        $manager->persist($comment1);

        $comment2 = (new Comment($user2))
            ->setQuestion($poll1)
            ->setCommentBody('Test for comment body 2')
            ->setCommentBodyHtml('<div>Comment Body HTML 2</div>')
            ->setPrivacy(BaseComment::PRIVACY_PRIVATE);
        $manager->persist($comment2);


        $comment3 = (new Comment($user2))
            ->setQuestion($poll5)
            ->setCommentBody('Test for comment body 3')
            ->setCommentBodyHtml('<div>Comment Body HTML 3</div>');
        $manager->persist($comment3);

        $comment4 = (new Comment($user1, $comment1))
            ->setQuestion($poll1)
            ->setCommentBody('Test for comment body 4')
            ->setCommentBodyHtml('<div>Comment Body HTML 4</div>');
        $manager->persist($comment4);

        $comment5 = (new Comment($user3, $comment1))
            ->setQuestion($poll1)
            ->setCommentBody('Test for comment body 5')
            ->setCommentBodyHtml('<div>Comment Body HTML 5</div>')
            ->setPrivacy(BaseComment::PRIVACY_PRIVATE);
        $manager->persist($comment5);

        $comment6 = (new Comment($user4, $comment1))
            ->setQuestion($poll1)
            ->setCommentBody('Test for comment body 6')
            ->setCommentBodyHtml('<div>Comment Body HTML 6</div>');
        $manager->persist($comment6);

        $manager->flush();

        $comment7 = (new Comment($user1, $comment2))
            ->setQuestion($poll1)
            ->setCommentBody('Test for comment body 7')
            ->setCommentBodyHtml('<div>Comment Body HTML 7</div>');
        $manager->persist($comment7);

        $manager->flush();

        $this->addReference('poll_comment_1', $comment1);
        $this->addReference('poll_comment_2', $comment2);
        $this->addReference('poll_comment_3', $comment3);
        $this->addReference('poll_comment_4', $comment4);
        $this->addReference('poll_comment_5', $comment5);
        $this->addReference('poll_comment_6', $comment6);
        $this->addReference('poll_comment_7', $comment7);
    }

    public function getDependencies()
    {
        return [LoadGroupQuestionData::class];
    }
}
