<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Poll\Comment;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Entity\UserPetition\Comment as MicropetitionComment;
use Civix\CoreBundle\Event\CommentEvent;
use Civix\CoreBundle\Event\CommentEvents;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CommentManager
{
    private $em;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EntityManager $em, EventDispatcherInterface $dispatcher)
    {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
    }

    public function updateRateToComment(BaseComment $comment, $user, $rateValue)
    {
        $rateCommentObj = $this->em
            ->getRepository('CivixCoreBundle:Poll\CommentRate')
            ->findOneBy(array('user' => $user, 'comment' => $comment));

        if (!$rateCommentObj) {
            $rateCommentObj = $this->em
                ->getRepository('CivixCoreBundle:Poll\CommentRate')
                ->addRateToComment($comment, $user, $rateValue);
        } else {
            $rateCommentObj->setRateValue($rateValue);
        }

        $this->em->persist($rateCommentObj);
        $this->em->flush();

        $comment = $this->updateRateSumForComment($comment);
        $comment->setRateStatus($rateValue);

        return $comment;
    }

    public function updateRateSumForComment(BaseComment $comment)
    {
        $rateSumArr = $this->em
            ->getRepository('CivixCoreBundle:Poll\CommentRate')
            ->calcRateCommentSum($comment);

        $comment->setRateSum($rateSumArr['rateSum']);
        $comment->setRatesCount($comment->getRates()->count());
        $this->em->persist($comment);
        $this->em->flush();

        return $comment;
    }

    public function addCommentByQuestionAnswer(Answer $answer)
    {
        $parent = $this->em->getRepository('CivixCoreBundle:Poll\Comment')
            ->findOneBy(array(
                'question' => $answer->getQuestion(),
                'parentComment' => null,
            ));

        if ($answer->getComment()) {
            $comment = new Comment();
            $comment->setUser($answer->getUser())
                ->setCommentBody($answer->getComment())
                ->setQuestion($answer->getQuestion())
                ->setPrivacy($answer->getPrivacy())
                ->setParentComment($parent)
            ;

            return $this->saveComment($comment);
        }
    }

    public function addUserPetitionRootComment(UserPetition $petition)
    {
        $comment = new MicropetitionComment();
        $comment->setPetition($petition);
        $comment->setCommentBody($petition->getBody());
        $comment->setUser($petition->getUser());

        return $this->saveComment($comment);
    }

    public function addPostRootComment(Post $post)
    {
        $comment = new Post\Comment();
        $comment->setPost($post);
        $comment->setCommentBody($post->getBody());
        $comment->setUser($post->getUser());

        return $this->saveComment($comment);
    }

    public function addPollRootComment(Question $question, $message = '')
    {
        $comment = new Comment();
        $comment
            ->setQuestion($question)
            ->setCommentBody($message)
        ;

        return $this->saveComment($comment);
    }

    public function addComment(BaseComment $comment)
    {
        $this->em->persist($comment);
        $this->em->flush($comment);

        $event = new CommentEvent($comment);
        $this->dispatcher->dispatch(CommentEvents::CREATE, $event);

        return $comment;
    }

    public function saveComment(BaseComment $comment)
    {
        $this->em->persist($comment);
        $this->em->flush($comment);

        return $comment;
    }

    public function deleteComment(BaseComment $comment)
    {
        $comment->setCommentBody('Deleted by author');
        $this->em->persist($comment);
        $this->em->flush($comment);

        return $comment;
    }
}