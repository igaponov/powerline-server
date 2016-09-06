<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Poll;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Event;
use Civix\CoreBundle\Service\SocialActivityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SocialActivitySubscriber implements EventSubscriberInterface
{
    /**
     * @var SocialActivityManager
     */
    private $manager;

    public function __construct(SocialActivityManager $manager)
    {
        $this->manager = $manager;
    }

    public static function getSubscribedEvents()
    {
        return [
            Event\UserEvents::FOLLOWED => ['sendUserFollowRequest', -100],
            Event\GroupEvents::PERMISSIONS_CHANGED => ['noticeGroupsPermissionsChanged', -100],
            Event\UserPetitionEvents::PETITION_CREATE => ['noticeUserPetitionCreated', -100],
            Event\PostEvents::POST_CREATE => ['noticePostCreated', -100],
            Event\CommentEvents::CREATE => [
                ['noticeEntityCommented', -100],
                ['noticeCommentMentioned', -100],
            ]
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
    }

    public function noticePostCreated(Event\PostEvent $event)
    {
        $this->manager->noticePostCreated($event->getPost());
    }

    public function noticeEntityCommented(Event\CommentEvent $event)
    {
        $comment = $event->getComment();
        if ($comment instanceof Poll\Comment) {
            $this->manager->noticePollCommented($comment);
        } elseif ($comment instanceof UserPetition\Comment) {
            $this->manager->noticeUserPetitionCommented($comment);
        } elseif ($comment instanceof Post\Comment) {
            $this->manager->noticePostCommented($comment);
        }
    }

    public function noticeCommentMentioned(Event\CommentEvent $event)
    {
        $comment = $event->getComment();
        $this->manager->noticeCommentMentioned($comment);
    }
}