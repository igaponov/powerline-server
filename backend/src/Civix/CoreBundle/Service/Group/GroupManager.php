<?php

namespace Civix\CoreBundle\Service\Group;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Invites\UserToGroup;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Entity\UserGroupManager;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Event\AvatarEvent;
use Civix\CoreBundle\Event\AvatarEvents;
use Civix\CoreBundle\Event\GroupEvent;
use Civix\CoreBundle\Event\GroupEvents;
use Civix\CoreBundle\Event\GroupUserEvent;
use Civix\CoreBundle\Event\InquiryEvent;
use Civix\CoreBundle\Event\InviteEvent;
use Civix\CoreBundle\Event\InviteEvents;
use Civix\CoreBundle\Exception\LogicException;
use Civix\CoreBundle\Model\Group\Worksheet;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GroupManager
{
    private const PERMISSION_PRIORITY = [
        'permissions_name',
        'permissions_address',
        'permissions_city',
        'permissions_state',
        'permissions_country',
        'permissions_zip_code',
        'permissions_email',
        'permissions_phone',
        'permissions_responses'
    ];

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(
        EntityManager $em,
        EventDispatcherInterface $dispatcher
    )
    {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
    }

    public function create(Group $group): void
    {
        $event = new AvatarEvent($group);
        $this->dispatcher->dispatch(AvatarEvents::CHANGE, $event);

        $this->em->persist($group);
        $this->em->flush();

        $event = new GroupEvent($group);
        $this->dispatcher->dispatch(GroupEvents::CREATED, $event);
    }

    public function save(Group $group): Group
    {
        $event = new AvatarEvent($group);
        $this->dispatcher->dispatch(AvatarEvents::CHANGE, $event);

        $this->em->persist($group);
        $this->em->flush();

        return $group;
    }

    public function delete(Group $group): void
    {
        $event = new GroupEvent($group);
        $this->dispatcher->dispatch(GroupEvents::BEFORE_DELETE, $event);

        $this->em->remove($group);
        $this->em->flush();
    }

    /**
     * @param Worksheet $worksheet
     * @return UserGroup
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function inquire(Worksheet $worksheet): UserGroup
    {
        $user = $worksheet->getUser();
        $group = $worksheet->getGroup();
        $userGroup = $this->joinToGroup($user, $group);

        //save fields values
        if ($group->getFillFieldsRequired()) {
            foreach ($worksheet->getAnsweredFields() as $field) {
                $groupField = $this->em->getRepository(Group\GroupField::class)
                    ->find($field->getId());
                $fieldValue = new Group\FieldValue();
                $fieldValue->setField($groupField);
                $fieldValue->setFieldValue($field->getValue());
                $fieldValue->setUser($user);
                $this->em->persist($fieldValue);
            }
        }
        $this->em->flush();

        $event = new InquiryEvent($worksheet);
        $this->dispatcher->dispatch(GroupEvents::USER_INQUIRED, $event);

        return $userGroup;
    }

    public function joinToGroup(User $user, Group $group): ?UserGroup
    {
        if ($group->isLocal()) {
            throw new \DomainException("User can't join a local group.");
        }

        $userGroup = null;
        if ($group->getId()) {
            //current status Group
            $userGroup = $this->em->getRepository('CivixCoreBundle:UserGroup')
                ->isJoinedUser($group, $user);
        }

        //check if user is joined yet and want to join
        if ($userGroup) {
            return $userGroup;
        }

        $userGroup = new UserGroup($user, $group);
        if ($user->getInvites()->contains($group)) {
            $userGroup->setStatus(UserGroup::STATUS_ACTIVE);
            $user->removeInvite($group);
        }
        $userGroup->setPermissionsByGroup($group);

        $this->em->persist($userGroup);
        $this->em->flush($userGroup);

        $event = new GroupUserEvent($group, $user);
        $this->dispatcher->dispatch(GroupEvents::USER_JOINED, $event);

        return $userGroup;
    }

    public function unjoinGroup(User $user, Group $group): void
    {
        $event = new GroupUserEvent($group, $user);
        $this->dispatcher->dispatch(GroupEvents::USER_BEFORE_UNJOIN, $event);
        
        $this->em->createQueryBuilder()
            ->delete(UserGroup::class, 'ug')
            ->where('ug.group = :group AND ug.user = :user')
            ->setParameter('group', $group)
            ->setParameter('user', $user)
            ->getQuery()->execute();

        $this->dispatcher->dispatch(GroupEvents::USER_UNJOIN, $event);
    }

    /**
     * Check if $group not fixed (may to join and unjoin without limiations).
     * 
     * @param Group $group
     * 
     * @return bool
     */
    public function isCommonGroup(Group $group): bool
    {
        return $group->getGroupType() === Group::GROUP_TYPE_COMMON;
    }

    /**
     * Check if $group private (need passcode) and not invited user.
     *
     * @param Group $group
     * @param User $user
     *
     * @return bool
     *
     * @deprecated to be removed in 2.0
     */
    public function isNeedCheckPasscode(Group $group, User $user): bool
    {
        return $group->getMembershipControl() === Group::GROUP_MEMBERSHIP_PASSCODE &&
            !$user->getInvites()->contains($group)
        ;
    }

    public function isMorePermissions($previousPermissions, $newPermissions): bool
    {
        $oldSumPriorityValue = 0;
        $newSumPriorityValue = 0;

        foreach (self::PERMISSION_PRIORITY as $priority => $key) {
            $oldSumPriorityValue += $this->calcPriorityValue(
                $previousPermissions, $key, $priority
            );
            $newSumPriorityValue += $this->calcPriorityValue(
                $newPermissions, $key, $priority
            );
        }

        return $oldSumPriorityValue < $newSumPriorityValue;
    }

    private function calcPriorityValue($permissions, $key, $priority): int
    {
        return (in_array($key, $permissions, true) ? 1 : 0) * (10 ** $priority);
    }

    public function changeMembershipControl(Group $group): Group
    {
        $this->em->persist($group);
        $this->em->flush();
        
        $event = new GroupEvent($group);
        $this->dispatcher->dispatch(GroupEvents::MEMBERSHIP_CONTROL_CHANGED, $event);
        
        return $group;
    }

    public function changePermissionSettings(Group $group): Group
    {
        $uow = $this->em->getUnitOfWork();
        $changeSet = $uow->getOriginalEntityData($group);

        $group->setPermissionsChangedAt(new \DateTime());
        $this->em->persist($group);
        $this->em->flush();

        $isMore = $this->isMorePermissions(
            $changeSet['requiredPermissions'],
            $group->getRequiredPermissions()
        );
        
        if ($isMore) {
            $event = new GroupEvent($group);
            $this->dispatcher->dispatch(GroupEvents::PERMISSIONS_CHANGED, $event);
        }
        
        return $group;
    }

    /**
     * @param Group $group
     * @param User $inviter
     * @param string|array $userNames
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function joinUsersByUsernameOrEmail(Group $group, User $inviter, $userNames): void
    {
        $iterator = $this->em->getRepository(User::class)
            ->findForInviteByGroupWithUsernameOrEmail($group, $userNames);
        $this->inviteUsersToGroup($group, $inviter, $iterator);
    }

    public function joinUsersByPostUpvotes(Group $group, User $inviter, Post $post): void
    {
        if ($post->isSupportersWereInvited()) {
            throw new \RuntimeException('Supporters were already invited for this post.');
        }
        $iterator = $this->em->getRepository(User::class)
            ->findForInviteByPostUpvotes($group, $post);
        $post->setSupportersWereInvited(true);
        $this->em->persist($post);
        $this->inviteUsersToGroup($group, $inviter, $iterator);
    }

    public function joinUsersByUserPetitionSignatures(Group $group, User $inviter, UserPetition $petition): void
    {
        if ($petition->isSupportersWereInvited()) {
            throw new \RuntimeException('Supporters were already invited for this petition.');
        }
        $iterator = $this->em->getRepository(User::class)
            ->findForInviteByUserPetitionSignatures($group, $petition);
        $petition->setSupportersWereInvited(true);
        $this->em->persist($petition);
        $this->inviteUsersToGroup($group, $inviter, $iterator);
    }

    public function changeUserStatus(UserGroup $userGroup, $status): void
    {
        switch ($status) {
            case UserGroup::STATUS_ACTIVE:
                $this->approveUser($userGroup);
                break;
            case UserGroup::STATUS_BANNED:
                $this->banUser($userGroup);
                break;
        }
    }

    public function approveUser(UserGroup $userGroup): void
    {
        $userGroup->setStatus(UserGroup::STATUS_ACTIVE);
        $this->em->persist($userGroup);
        $this->em->flush();

        $event = new GroupUserEvent($userGroup->getGroup(), $userGroup->getUser());
        $this->dispatcher->dispatch(GroupEvents::USER_JOINED, $event);
    }

    public function banUser(UserGroup $userGroup): void
    {
        $userGroup->setStatus(UserGroup::STATUS_BANNED);
        $this->em->persist($userGroup);
        $this->em->flush();
    }

    public function removeInvite(Group $group, User $user): void
    {
        if ($user->getInvites()->contains($group)) {
            $user->removeInvite($group);
            $this->em->persist($user);
            $this->em->flush();
        }
    }

    public function addGroupManager(Group $group, User $user): UserGroupManager
    {
        if(!$group->isMember($user))
        {
            throw new \RuntimeException('The user is not member of the group');
        }
        if($group->isManager($user))
        {
            throw new \RuntimeException('The user is already group manager of this group');
        }

        $userGroupManager = new UserGroupManager($user, $group);
        $userGroupManager->setStatus(UserGroupManager::STATUS_ACTIVE);
        $group->addManager($userGroupManager);

        $this->em->persist($group);
        $this->em->flush();

        return $userGroupManager;
    }

    public function deleteGroupManager(Group $group, User $user): void
    {
        if(!$group->isManager($user))
        {
            throw new \RuntimeException('The user is not a group manager of this group');
        }

        $userGroupManager = $this->em->getRepository(UserGroupManager::class)
            ->findOneBy(['group' => $group, 'user' => $user]);

        $this->em->remove($userGroupManager);
        $this->em->flush();
    }

    public function addTag(Group $group, Group\Tag $tag): void
    {
        if ($group->getTags()->count() === 5) {
            throw new LogicException('Group should contain 5 tags or less.');
        }
        if (!$group->getTags()->contains($tag)) {
            $group->addTag($tag);
            $this->em->flush();
        }
    }

    public function removeTag(Group $group, Group\Tag $tag): void
    {
        $group->removeTag($tag);
        $this->em->flush();
    }

    private function inviteUserToGroup(User $user, Group $group, User $inviter): UserToGroup
    {
        $invite = new UserToGroup();
        $invite->setInviter($inviter);
        $invite->setUser($user);
        $invite->setGroup($group);
        $this->em->persist($invite);
        $event = new InviteEvent($invite);
        $this->dispatcher->dispatch(InviteEvents::CREATE, $event);

        return $invite;
    }

    /**
     * @param Group $group
     * @param User $inviter
     * @param $iterator
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function inviteUsersToGroup(Group $group, User $inviter, iterable $iterator): void
    {
        $count = 0;
        $max = 50;
        /** @var UserToGroup[] $invites */
        $invites = [];
        foreach ($iterator as $item) {
            $user = $item[0];
            $invites[] = $this->inviteUserToGroup($user, $group, $inviter);
            $count++;
            if ($count === $max) {
                $count = 0;
                $this->em->flush();
                foreach ($invites as $invite) {
                    $this->em->detach($invite->getUser());
                    $this->em->detach($invite);
                }
            }
        }
        $this->em->flush();
    }
}
