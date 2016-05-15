<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Group;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Poll\Comment;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Stripe\CustomerGroup;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
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
            'question-comment',
            $this->createComment(
                $this->getReference('userfollowtest1'),
                $this->getReference('group-question')
            )
        );
        $this->createComment(
            $this->getReference('userfollowtest2'),
            $this->getReference('group-question'),
            $this->getReference('question-comment')
        );
        $this->createComment(
            $this->getReference('userfollowtest3'),
            $this->getReference('group-question')
        );
        $this->addReference(
            'testfollowsecretgroups-comment', 
            $this->createComment(
                $this->getReference('followertest'),
                $this->getReference('testfollowsecretgroups-question')
            )
        );
        $this->createComment(
            $this->getReference('testuserbookmark1'),
            $this->getReference('testfollowsecretgroups-question'),
            $this->getReference('testfollowsecretgroups-comment')
        );
    }

    public function getDependencies()
    {
        return [LoadGroupData::class, LoadGroupQuestionData::class];
    }

    /**
     * @param object|User $user
     * @param object|Question $question
     * @param null|Comment $parentComment
     * @return CustomerGroup
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