<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\BaseCommentRate;
use Civix\CoreBundle\Entity\Karma;
use Civix\CoreBundle\Entity\Post\Vote;
use Civix\CoreBundle\Event;
use Civix\CoreBundle\Repository\KarmaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class KarmaSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var KarmaRepository
     */
    private $repository;

    public static function getSubscribedEvents()
    {
        return [
            Event\UserEvents::VIEW_REPRESENTATIVES => 'representativeScreen',
            Event\UserEvents::FOLLOW => 'follow',
            Event\UserEvents::FOLLOW_REQUEST_APPROVE => 'approveFollowRequest',
            Event\GroupEvents::USER_JOINED => 'joinGroup',
            Event\PostEvents::POST_CREATE => 'createPost',
            Event\PollEvents::QUESTION_ANSWER => 'answerPoll',
            Event\PostEvents::POST_VOTE => 'receiveUpvoteOnPost',
            Event\CommentEvents::RATE => 'receiveUpvoteOnComment',
            Event\AnnouncementEvents::MARK_AS_READ => 'viewAnnouncement',
        ];
    }

    public function __construct(EntityManagerInterface $em, KarmaRepository $repository)
    {
        $this->em = $em;
        $this->repository = $repository;
    }

    public function representativeScreen(Event\UserRepresentativeEvent $event)
    {
        $user = $event->getUser();
        $karma = $this->repository
            ->findOneByUserAndType($user, Karma::TYPE_REPRESENTATIVE_SCREEN);
        if (!$karma) {
            $karma = new Karma($user, Karma::TYPE_REPRESENTATIVE_SCREEN, 25);
            $this->em->persist($karma);
            $this->em->flush();
        }
    }

    public function follow(Event\UserFollowEvent $event)
    {
        $userFollow = $event->getUserFollow();
        $user = $userFollow->getFollower();
        $karma = $this->repository->findOneByUserAndType($user, Karma::TYPE_FOLLOW);
        if (!$karma) {
            $karma = new Karma($user, Karma::TYPE_FOLLOW, 10, ['following_id' => $userFollow->getUser()->getId()]);
            $this->em->persist($karma);
            $this->em->flush();
        }
    }

    public function approveFollowRequest(Event\UserFollowEvent $event)
    {
        $userFollow = $event->getUserFollow();
        $user = $userFollow->getUser();
        $karma = $this->repository
            ->findOneByUserAndType($user, Karma::TYPE_APPROVE_FOLLOW_REQUEST);
        if (!$karma) {
            $karma = new Karma($user, Karma::TYPE_APPROVE_FOLLOW_REQUEST, 10, ['follower_id' => $userFollow->getFollower()->getId()]);
            $this->em->persist($karma);
            $this->em->flush();
        }
    }

    public function joinGroup(Event\GroupUserEvent $event)
    {
        $user = $event->getUser();
        $group = $event->getGroup();
        $karma = $this->repository
            ->findOneByUserAndType($user, Karma::TYPE_JOIN_GROUP);
        if (!$karma) {
            $karma = new Karma($user, Karma::TYPE_JOIN_GROUP, 10, ['group_id' => $group->getId()]);
            $this->em->persist($karma);
            $this->em->flush();
        }
    }

    public function createPost(Event\PostEvent $event)
    {
        $post = $event->getPost();
        $user = $post->getUser();
        $karma = $this->repository
            ->findOneByUserAndType($user, Karma::TYPE_CREATE_POST);
        if (!$karma) {
            $karma = new Karma($user, Karma::TYPE_CREATE_POST, 10, ['post_id' => $post->getId()]);
            $this->em->persist($karma);
            $this->em->flush();
        }
    }

    public function answerPoll(Event\Poll\AnswerEvent $event)
    {
        $answer = $event->getAnswer();
        $user = $answer->getUser();
        $karma = new Karma($user, Karma::TYPE_ANSWER_POLL, 2, ['answer_id' => $answer->getId()]);
        $this->em->persist($karma);
        $this->em->flush();
    }

    public function receiveUpvoteOnPost(Event\Post\VoteEvent $event)
    {
        $vote = $event->getVote();

        if ($vote->getOption() !== Vote::OPTION_UPVOTE) {
            return;
        }

        $post = $vote->getPost();
        $user = $post->getUser();
        $karma = new Karma($user, Karma::TYPE_RECEIVE_UPVOTE_ON_POST, 2, [
            'post_id' => $post->getId(),
            'vote_id' => $vote->getId(),
        ]);
        $this->em->persist($karma);
        $this->em->flush();
    }

    public function receiveUpvoteOnComment(Event\RateEvent $event)
    {
        $rate = $event->getRate();
        $comment = $rate->getComment();
        $user = $comment->getUser();

        $metadata = [
            'type' => $comment->getEntityType(),
            'comment_id' => $comment->getId(),
            'rate_id' => $rate->getId(),
        ];
        $type = Karma::TYPE_RECEIVE_UPVOTE_ON_COMMENT;
        if ($rate->getRateValue() == BaseCommentRate::RATE_UP) {
            $karma = new Karma($user, $type, 2, $metadata);
            $this->em->persist($karma);
            $this->em->flush();
        } else {
            $this->repository->deleteByUserAndTypeAndMetadata($user, $type, $metadata);
        }
    }

    public function viewAnnouncement(Event\UserAnnouncementsEvent $event)
    {
        $user = $event->getUser();
        $announcements = $event->getAnnouncements();
        foreach ($announcements as $announcement) {
            $karma = new Karma($user, Karma::TYPE_VIEW_ANNOUNCEMENT, 2, [
                'announcement_id' => $announcement->getId(),
            ]);
            $this->em->persist($karma);
        }
        $this->em->flush();
    }
}