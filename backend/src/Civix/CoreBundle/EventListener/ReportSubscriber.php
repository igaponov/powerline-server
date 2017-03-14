<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Report\MembershipReport;
use Civix\CoreBundle\Entity\Report\PollResponseReport;
use Civix\CoreBundle\Entity\Report\UserReport;
use Civix\CoreBundle\Event\GroupEvents;
use Civix\CoreBundle\Event\GroupUserEvent;
use Civix\CoreBundle\Event\InquiryEvent;
use Civix\CoreBundle\Event\Poll\AnswerEvent;
use Civix\CoreBundle\Event\PollEvents;
use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
use Civix\CoreBundle\Event\UserFollowEvent;
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
            UserEvents::FOLLOW_REQUEST_APPROVE => 'updateUserReport',
            UserEvents::UNFOLLOW => 'updateUserReport',
            UserEvents::REGISTRATION => 'createUserReport',
            GroupEvents::USER_INQUIRED => 'createMembershipReport',
            GroupEvents::USER_UNJOIN => 'deleteMembershipReport',
            PollEvents::QUESTION_ANSWER => 'createPollReport',
        ];
    }

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function updateUserReport(UserFollowEvent $event)
    {
        $user = $event->getUserFollow()->getUser();
        $this->em->getRepository(UserReport::class)
            ->upsertUserReport($user, $user->getFollowers()->count());
    }

    public function createUserReport(UserEvent $event)
    {
        $this->em->getRepository(UserReport::class)
            ->upsertUserReport($event->getUser(), 0);
    }

    public function createMembershipReport(InquiryEvent $event)
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

    public function deleteMembershipReport(GroupUserEvent $event)
    {
        $this->em->getRepository(MembershipReport::class)
            ->deleteMembershipReport($event->getUser(), $event->getGroup());
    }

    public function createPollReport(AnswerEvent $event)
    {
        $this->em->getRepository(PollResponseReport::class)
            ->insertPollResponseReport($event->getAnswer());
    }
}