<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Poll;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Event;
use Civix\CoreBundle\Service\SocialActivityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SocialActivitySubscriber implements EventSubscriberInterface
{
    /**
     * @var SocialActivityManager
     */
    private $manager;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(SocialActivityManager $manager, EntityManagerInterface $em)
    {
        $this->manager = $manager;
        $this->em = $em;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Event\UserEvents::FOLLOW => ['sendUserFollowRequest', -100],
            Event\GroupEvents::PERMISSIONS_CHANGED => ['noticeGroupsPermissionsChanged', -100],
            Event\UserPetitionEvents::PETITION_CREATE => ['noticeUserPetitionCreated', -100],
            Event\PostEvents::POST_CREATE => [
                ['noticePostCreated', -100],
            ],
            Event\CommentEvents::CREATE => [
                ['noticeEntityCommented', -100],
            ],
            Event\PollEvents::QUESTION_ANSWER => ['noticeAnsweredToQuestion', -100],
            Event\GroupEvents::USER_JOINED => ['noticeGroupJoiningApproved', -100],
        ];
    }

    public function sendUserFollowRequest(Event\UserFollowEvent $event)
    {
        $this->manager->sendUserFollowRequest($event->getUserFollow());
    }

    public function noticeGroupsPermissionsChanged(Event\GroupEvent $event)
    {
        $this->manager->noticeGroupsPermissionsChanged($event->getGroup());
    }

    public function noticeUserPetitionCreated(Event\UserPetitionEvent $event)
    {
        $this->manager->noticeUserPetitionCreated($event->getPetition());
        $this->em->flush();
    }

    public function noticePostCreated(Event\PostEvent $event)
    {
        $this->manager->noticePostCreated($event->getPost());
        $this->em->flush();
    }

    public function noticeEntityCommented(Event\CommentEvent $event)
    {
        $comment = $event->getComment();
        if ($comment instanceof Poll\Comment) {
            $this->manager->noticePollCommented($comment);
            $this->manager->noticePollCommentReplied($comment);
            $this->manager->noticeOwnPollCommented($comment);
        } elseif ($comment instanceof UserPetition\Comment) {
            $this->manager->noticeUserPetitionCommented($comment);
            $this->manager->noticeUserPetitionCommentReplied($comment);
            $this->manager->noticeOwnUserPetitionCommented($comment);
        } elseif ($comment instanceof Post\Comment) {
            $this->manager->noticePostCommented($comment);
            $this->manager->noticePostCommentReplied($comment);
            $this->manager->noticeOwnPostCommented($comment);
        }
        $this->em->flush();
    }

    public function noticeAnsweredToQuestion(Event\Poll\AnswerEvent $event)
    {
        $answer = $event->getAnswer();
        $this->manager->noticeAnsweredToQuestion($answer);
    }

    public function noticeGroupJoiningApproved(Event\GroupUserEvent $event)
    {
        $this->manager->noticeGroupJoiningApproved($event->getUser(), $event->getGroup());
    }
}