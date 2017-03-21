<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Entity\UserGroupManager;
use Civix\CoreBundle\Event\GroupEvent;
use Civix\CoreBundle\Event\GroupEvents;
use Civix\CoreBundle\Event\GroupUserEvent;
use Civix\CoreBundle\Repository\UserGroupRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GroupEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserGroupRepository
     */
    private $userGroupRepository;
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(
        UserGroupRepository $userGroupRepository,
        EntityManager $em
    )
    {
        $this->userGroupRepository = $userGroupRepository;
        $this->em = $em;
    }

    public static function getSubscribedEvents()
    {
        return [
            GroupEvents::MEMBERSHIP_CONTROL_CHANGED => 'setApprovedAllUsersInGroup',
            GroupEvents::USER_UNJOIN => 'deleteGroupOwner',
        ];
    }

    public function setApprovedAllUsersInGroup(GroupEvent $event)
    {
        $group = $event->getGroup();
        if ($group->getMembershipControl() == Group::GROUP_MEMBERSHIP_PUBLIC) {
            $this->userGroupRepository->setApprovedAllUsersInGroup($group);
        }
    }

    public function deleteGroupOwner(GroupUserEvent $event)
    {
        $group = $event->getGroup();

        if (!$group->getOwner() instanceof User || $group->getOwner()->getId() != $event->getUser()->getId()) {
            return;
        }

        $userGroup = $this->em->getRepository(UserGroupManager::class)
            ->getOldestManager($group);
        if (!$userGroup) {
            $userGroup = $this->em->getRepository(UserGroup::class)
                ->getOldestMember($group);
        }
        $group->setOwner($userGroup ? $userGroup->getUser() : null);

        $this->em->persist($group);
        $this->em->flush();
    }
}