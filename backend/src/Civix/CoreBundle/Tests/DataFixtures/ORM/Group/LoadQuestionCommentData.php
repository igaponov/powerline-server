<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Group;

use Civix\CoreBundle\Entity\Poll\Comment;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadQuestionCommentData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /** @var ObjectManager */
    private $manager;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        $this->addReference(
            'question_comment_1',
            $this->createComment(
                $this->getReference('user_2'),
                $this->getReference('group_question_1')
            )
        );
        $this->addReference(
            'question_comment_2',
            $this->createComment(
                $this->getReference('user_3'),
                $this->getReference('group_question_1'),
                $this->getReference('question_comment_1')
            )
        );
        $this->addReference(
            'question_comment_3',
            $this->createComment(
                $this->getReference('user_4'),
                $this->getReference('group_question_1')
            )
        );
        $this->addReference(
            'question_comment_4',
            $this->createComment(
                $this->getReference('user_4'),
                $this->getReference('group_question_3')
            )
        );
        $this->createComment(
            $this->getReference('user_2'),
            $this->getReference('group_question_3'),
            $this->getReference('question_comment_4')
        );
    }

    public function getDependencies()
    {
        return [LoadGroupQuestionData::class];
    }

    /**
     * @param object|User $user
     * @param object|Question $question
     * @param null|Comment $parentComment
     * @return Comment
     */
    private function createComment($user, $question, $parentComment = null): Comment
    {
        $faker = Factory::create();
        $comment = new Comment($user, $parentComment);
        $comment->setQuestion($question);
        $comment->setCommentBody($faker->text);
        $comment->setCommentBodyHtml($faker->text);

        $this->manager->persist($comment);
        $this->manager->flush();

        return $comment;
    }
}