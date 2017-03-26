<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Report\MembershipReport;
use Civix\CoreBundle\Entity\Report\PetitionResponseReport;
use Civix\CoreBundle\Entity\Report\PollResponseReport;
use Civix\CoreBundle\Entity\Report\PostResponseReport;
use Civix\CoreBundle\Entity\Report\UserReport;
use Civix\CoreBundle\Event;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    public static function getSubscribedEvents()
    {
        return [
            Event\GroupEvents::USER_INQUIRED => 'createMembershipReport',
            Event\GroupEvents::USER_JOINED => 'updateUserGroupReport',
            Event\GroupEvents::USER_UNJOIN => 'deleteMembershipReport',
            Event\PollEvents::QUESTION_ANSWER => 'createPollReport',
            Event\PostEvents::POST_VOTE => 'createPostReport',
            Event\PostEvents::POST_UNVOTE => 'deletePostReport',
            Event\UserEvents::FOLLOW_REQUEST_APPROVE => 'updateUserReport',
            Event\UserEvents::REGISTRATION => 'createUserReport',
            Event\UserEvents::UNFOLLOW => 'updateUserReport',
        ];
    }

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function updateUserReport(Event\UserFollowEvent $event)
    {
        $user = $event->getUserFollow()->getUser();
        $this->em->getRepository(UserReport::class)
            ->upsertUserReport($user, $user->getFollowers()->count());
    }

    public function createUserReport(Event\UserEvent $event)
    {
        $this->em->getRepository(UserReport::class)
            ->upsertUserReport($event->getUser(), 0);
    }

    public function createMembershipReport(Event\InquiryEvent $event)
    {
        $worksheet = $event->getWorksheet();
        $user = $worksheet->getUser();
        $group = $worksheet->getGroup();
        $answeredFields = $worksheet->getAnsweredFields();
        $fields = [];
        foreach ($answeredFields as $field) {
            $fields[$field->getId()] = $field->getValue();
        }
        $this->em->getRepository(MembershipReport::class)
            ->upsertMembershipReport($user, $group, $fields);
    }

    public function deleteMembershipReport(Event\GroupUserEvent $event)
    {
        $this->em->getRepository(MembershipReport::class)
            ->deleteMembershipReport($event->getUser(), $event->getGroup());
    }

    public function createPollReport(Event\Poll\AnswerEvent $event)
    {
        $this->em->getRepository(PollResponseReport::class)
            ->insertPollResponseReport($event->getAnswer());
    }

    public function updateUserGroupReport(Event\GroupUserEvent $event)
    {
        $group = $event->getGroup();
        $user = $event->getUser();

        $country = $state = $locality = null;
        switch ($group->getGroupType()) {
            case Group::GROUP_TYPE_COUNTRY:
                $country = $group->getOfficialName();
                break;
            case Group::GROUP_TYPE_STATE:
                $state = $group->getOfficialName();
                break;
            case Group::GROUP_TYPE_LOCAL:
                $locality = $group->getOfficialName();
                break;
            default:
                return;
        }

        $this->em->getRepository(UserReport::class)
            ->upsertUserReport($user, null, null, $country, $state, $locality);
    }

    public function createPostReport(Event\Post\VoteEvent $event)
    {
        $this->em->getRepository(PostResponseReport::class)
            ->insertPostResponseReport($event->getVote());
    }

    public function deletePostReport(Event\Post\VoteEvent $event)
    {
        $this->em->getRepository(PostResponseReport::class)
            ->deletePostResponseReport($event->getVote());
    }
}