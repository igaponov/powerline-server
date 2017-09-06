<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\BaseCommentRate;
use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Poll\Comment;
use Civix\CoreBundle\Entity\Poll\CommentRate;
use Civix\CoreBundle\Event\CommentEvent;
use Civix\CoreBundle\Event\CommentEvents;
use Civix\CoreBundle\Event\RateEvent;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CommentManager
{
    const DELETED_COMMENT_BODY = 'Deleted by author';

    /**
     * @var EntityManager
     */
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

    /**
     * @param Comment $comment
     * @param $user
     * @param $rateValue
     * @return CommentRate|null|object
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @deprecated Delete in next version
     */
    public function updateRateToComment(Comment $comment, $user, $rateValue)
    {
        $rateCommentObj = $this->em
            ->getRepository('CivixCoreBundle:Poll\CommentRate')
            ->findOneBy(array('user' => $user, 'comment' => $comment));

        if (!$rateCommentObj) {
            $rateCommentObj = new CommentRate();
            $rateCommentObj->setRateValue($rateValue);
            $rateCommentObj->setComment($comment);
            $rateCommentObj->setUser($user);
            $this->em->persist($rateCommentObj);
        } else {
            $rateCommentObj->setRateValue($rateValue);
        }

        $rateStatistics = $this->em
            ->getRepository('CivixCoreBundle:Poll\CommentRate')
            ->getRateStatistics($comment);
        $comment->setRateSum($rateStatistics['rateSum']);
        $comment->setRatesCount($rateStatistics['rateCount']);

        $this->em->flush();

        return $rateCommentObj;
    }

    public function addCommentByQuestionAnswer(Answer $answer): void
    {
        if ($answer->getComment()) {
            $comment = new Comment($answer->getUser());
            $comment->setQuestion($answer->getQuestion())
                ->setCommentBody($answer->getComment())
                ->setPrivacy($answer->getPrivacy());
            $this->saveComment($comment);
        }
    }

    public function addComment(BaseComment $comment): BaseComment
    {
        $event = new CommentEvent($comment);
        $this->dispatcher->dispatch(CommentEvents::PRE_CREATE, $event);

        $this->em->persist($comment);
        $this->em->flush($comment);

        $this->dispatcher->dispatch('async.'.CommentEvents::CREATE, $event);

        return $comment;
    }

    public function saveComment(BaseComment $comment): BaseComment
    {
        $event = new CommentEvent($comment);
        $this->dispatcher->dispatch(CommentEvents::PRE_UPDATE, $event);

        $this->em->persist($comment);
        $this->em->flush($comment);

        $this->dispatcher->dispatch('async.'.CommentEvents::UPDATE, $event);

        return $comment;
    }

    public function deleteComment(BaseComment $comment): BaseComment
    {
        $comment->setCommentBody(self::DELETED_COMMENT_BODY);
        $comment->setCommentBodyHtml(self::DELETED_COMMENT_BODY);
        $this->em->persist($comment);
        $this->em->flush($comment);

        return $comment;
    }

    public function rateComment(BaseComment $comment, BaseCommentRate $rate): BaseComment
    {
        if (!$this->em->contains($rate)) {
            $comment->addRate($rate);
            $this->em->persist($rate);
            $changed = true;
        } else {
            // check if rate was changed
            $metadata = $this->em->getClassMetadata(get_class($rate));
            $uow = $this->em->getUnitOfWork();
            $uow->recomputeSingleEntityChangeSet($metadata, $rate);
            $changed = isset($uow->getEntityChangeSet($rate)['rateValue']);
        }

        if ($changed) {
            $this->em->flush($rate);

            $event = new RateEvent($rate);
            $this->dispatcher->dispatch(CommentEvents::RATE, $event);
        }

        return $comment;
    }
}
