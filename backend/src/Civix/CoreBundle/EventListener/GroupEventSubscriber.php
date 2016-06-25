<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Event\GroupEvent;
use Civix\CoreBundle\Event\GroupEvents;
use Civix\CoreBundle\Repository\UserGroupRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GroupEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserGroupRepository
     */
    private $userGroupRepository;

    public function __construct(
        UserGroupRepository $userGroupRepository
    )
    {
        $this->userGroupRepository = $userGroupRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            GroupEvents::MEMBERSHIP_CONTROL_CHANGED => 'setApprovedAllUsersInGroup',
        ];
    }

    public function setApprovedAllUsersInGroup(GroupEvent $event)
    {
        $group = $event->getGroup();
        if ($group->getMembershipControl() == Group::GROUP_MEMBERSHIP_PUBLIC) {
            $this->userGroupRepository->setApprovedAllUsersInGroup($group);
        }
    }
}