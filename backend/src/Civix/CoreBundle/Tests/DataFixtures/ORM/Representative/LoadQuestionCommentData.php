<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Representative;

use Civix\CoreBundle\Entity\Poll\Comment;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class LoadQuestionCommentData extends AbstractFixture implements DependentFixtureInterface
{
    /** @var ObjectManager */
    private $manager;

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        $this->addReference(
            'representative_question_comment_1',
            $this->createComment(
                $this->getReference('user_2'),
                $this->getReference('representative_question_1')
            )
        );
        $this->createComment(
            $this->getReference('user_3'),
            $this->getReference('representative_question_1'),
            $this->getReference('representative_question_comment_1')
        );
        $this->addReference(
            'representative_question_comment_3',
            $this->createComment(
                $this->getReference('user_4'),
                $this->getReference('representative_question_1')
            )
        );
        $this->addReference(
            'representative_question_comment_4',
            $this->createComment(
                $this->getReference('user_4'),
                $this->getReference('representative_question_3')
            )
        );
        $this->createComment(
            $this->getReference('user_2'),
            $this->getReference('representative_question_3'),
            $this->getReference('representative_question_comment_4')
        );
    }

    public function getDependencies()
    {
        return [LoadRepresentativeQuestionData::class];
    }

    /**
     * @param object|User $user
     * @param object|Question $question
     * @param null|Comment $parentComment
     * @return Comment
     */
    private function createComment($user, $question, $parentComment = null)
    {
        $faker = Factory::create();
        $comment = new Comment();
        $comment->setUser($user);
        $comment->setQuestion($question);
        $comment->setCommentBody($faker->text);
        $comment->setCommentBodyHtml($faker->text);
        if ($parentComment) {
            $comment->setParentComment($parentComment);
        }

        $this->manager->persist($comment);
        $this->manager->flush();

        return $comment;
    }
}