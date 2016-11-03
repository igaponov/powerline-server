<?php

namespace Civix\CoreBundle\Service\Group;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Invites\UserToGroup;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Entity\UserGroupManager;
use Civix\CoreBundle\Event\GroupEvent;
use Civix\CoreBundle\Event\GroupEvents;
use Civix\CoreBundle\Event\GroupUserEvent;
use Civix\CoreBundle\Event\InquiryEvent;
use Civix\CoreBundle\Event\InviteEvent;
use Civix\CoreBundle\Event\InviteEvents;
use Civix\CoreBundle\Model\Group\Worksheet;
use Civix\CoreBundle\Service\Google\Geocode;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GroupManager
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Geocode
     */
    private $geocode;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    
    private $permissionPriority = [
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

    public function __construct(
        EntityManager $entityManager, 
        Geocode $geocode,
        EventDispatcherInterface $dispatcher
    )
    {
        $this->entityManager = $entityManager;
        $this->geocode = $geocode;
        $this->dispatcher = $dispatcher;
    }

    public function register(Group $group)
    {
        $this->entityManager->persist($group);
        $this->entityManager->flush();

        $event = new GroupEvent($group);
        $this->dispatcher->dispatch(GroupEvents::REGISTERED, $event);
    }

    public function create(Group $group)
    {
        $this->entityManager->persist($group);
        $this->entityManager->flush();

        $event = new GroupEvent($group);
        $this->dispatcher->dispatch(GroupEvents::CREATED, $event);
    }

    /**
     * @param Worksheet $worksheet
     * @return UserGroup
     */
    public function inquire(Worksheet $worksheet)
    {
        $userGroup = $this->joinToGroup($worksheet->getUser(), $worksheet->getGroup());

        $event = new InquiryEvent($worksheet);
        $this->dispatcher->dispatch(GroupEvents::USER_INQUIRED, $event);

        return $userGroup;
    }

    public function joinToGroup(User $user, Group $group)
    {
        //current status Group
        $userGroup = $this->entityManager
            ->getRepository('CivixCoreBundle:UserGroup')
            ->isJoinedUser($group, $user);

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
        $this->entityManager->createQueryBuilder()
            ->delete(UserToGroup::class, 'i')
            ->where('i.user = :user AND i.group = :group')
            ->setParameter('user', $user)
            ->setParameter('group', $group)
            ->getQuery()->execute();
        $this->entityManager->persist($userGroup);
        $this->entityManager->flush($userGroup);

        $event = new GroupUserEvent($group, $user);
        $this->dispatcher->dispatch(GroupEvents::USER_JOINED, $event);

        return $userGroup;
    }

    public function unjoinGroup(User $user, Group $group)
    {
        $event = new GroupUserEvent($group, $user);
        $this->dispatcher->dispatch(GroupEvents::USER_BEFORE_UNJOIN, $event);
        
        $this->entityManager->createQueryBuilder()
            ->delete(UserGroup::class, 'ug')
            ->where('ug.group = :group AND ug.user = :user')
            ->setParameter('group', $group)
            ->setParameter('user', $user)
            ->getQuery()->execute();
    }

    /**
     * Check if $group not fixed (may to join and unjoin without limiations).
     * 
     * @param \Civix\CoreBundle\Entity\Group $group
     * 
     * @return bool
     */
    public function isCommonGroup(Group $group)
    {
        return $group->getGroupType() === Group::GROUP_TYPE_COMMON;
    }

    /**
     * Check if $group private (need passcode) and not invited user.
     *
     * @param \Civix\CoreBundle\Entity\Group $group
     * @param User $user
     *
     * @return bool
     *
     * @deprecated to be removed in 2.0
     */
    public function isNeedCheckPasscode(Group $group, User $user)
    {
        return $group->getMembershipControl() === Group::GROUP_MEMBERSHIP_PASSCODE &&
            !$user->getInvites()->contains($group)
        ;
    }

    /**
     * @author Habibillah <habibillah@gmail.com>
     * @param User $user
     * @return User
     */
    public function autoJoinUser(User $user)
    {
        $this->resetGeoGroups($user);

        $query = $user->getAddressQuery();
        $repository = $this->entityManager->getRepository('CivixCoreBundle:Group');

        $country = $this->geocode->getCountry($query);
        if ($country) {
            $countryGroup = $repository->getCountryGroup($country);
        } else {
            $countryGroup = $repository->findCountryGroup($user->getCountry());
        }

        if (!$countryGroup) {
            return $user;
        }

        $this->joinToGroup($user, $countryGroup);

        $parentCountryGroup = $countryGroup->getParent();
        if (!is_null($parentCountryGroup) &&
            ($parentCountryGroup->getLocationName() == Group::GROUP_LOCATION_NAME_EROPEAN_UNION
                || $parentCountryGroup->getLocationName() == Group::GROUP_LOCATION_NAME_AFRICAN_UNION)) {
            $this->joinToGroup($user, $parentCountryGroup);
        }

        $state = $this->geocode->getState($query);
        if ($state) {
            $stateGroup = $repository->getStateGroup($state, $countryGroup);
        } else {
            $stateGroup = $repository->findStateGroup($user->getState(), $countryGroup);
        }

        if ($stateGroup) {
            $this->joinToGroup($user, $stateGroup);
        }

        $locality = $this->geocode->getLocality($query);
        if ($locality) {
            $localityGroup = $repository->getLocalGroup($locality, $stateGroup ? $stateGroup : $countryGroup);
        } else {
            $localityGroup = $repository->findLocalGroup($user->getCity(), $stateGroup ? $stateGroup : $countryGroup);
        }

        if ($localityGroup) {
            $this->joinToGroup($user, $localityGroup);
        }

        return $user;
    }

    public function isMorePermissions($previousPermissions, $newPermissions)
    {
        $oldSumPriorityValue = 0;
        $newSumPriorityValue = 0;

        foreach ($this->permissionPriority as $priority => $key) {
            $oldSumPriorityValue += $this->calcPriorityValue(
                $previousPermissions, $key, $priority
            );
            $newSumPriorityValue += $this->calcPriorityValue(
                $newPermissions, $key, $priority
            );
        }

        return $oldSumPriorityValue < $newSumPriorityValue;
    }

    public function resetGeoGroups(User $user)
    {
        $userGroups = $this->entityManager->getRepository(UserGroup::class)->getGeoUserGroups($user);
        if (!empty($userGroups)) {
            $this->entityManager->createQueryBuilder()
                ->delete(UserGroup::class, 'ug')
                ->where('ug.id IN (:ids)')
                ->setParameter('ids', array_map(function (UserGroup $userGroup) {
                    return $userGroup->getId();
                }, $userGroups))
                ->getQuery()->execute();
        }
    }

    private function calcPriorityValue($permissions, $key, $priority)
    {
        return (array_search($key, $permissions) !== false ? 1 : 0) * pow(10, $priority);
    }

    public function changeMembershipControl(Group $group)
    {
        $this->entityManager->persist($group);
        $this->entityManager->flush();
        
        $event = new GroupEvent($group);
        $this->dispatcher->dispatch(GroupEvents::MEMBERSHIP_CONTROL_CHANGED, $event);
        
        return $group;
    }

    public function changePermissionSettings(Group $group)
    {
        $uow = $this->entityManager->getUnitOfWork();
        $changeSet = $uow->getOriginalEntityData($group);

        $group->setPermissionsChangedAt(new \DateTime());
        $this->entityManager->persist($group);
        $this->entityManager->flush();

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
     */
    public function joinUsersByUsername(Group $group, User $inviter, $userNames)
    {
        $users = $this->entityManager->getRepository(User::class)
            ->findForInviteByGroupUsername($group, $userNames);
        foreach ($users as $user) {
            $invite = new UserToGroup();
            $invite->setInviter($inviter);
            $invite->setUser($user);
            $invite->setGroup($group);
            $this->entityManager->persist($invite);
            $event = new InviteEvent($invite);
            $this->dispatcher->dispatch(InviteEvents::CREATE, $event);
        }
        $this->entityManager->flush();
    }

    public function approveUser(UserGroup $userGroup)
    {
        $userGroup->setStatus(UserGroup::STATUS_ACTIVE);
        $this->entityManager->persist($userGroup);
        $this->entityManager->flush();

        $event = new GroupUserEvent($userGroup->getGroup(), $userGroup->getUser());
        $this->dispatcher->dispatch(GroupEvents::USER_JOINED, $event);
    }

    public function removeInvite(Group $group, User $user)
    {
        if ($user->getInvites()->contains($group)) {
            $user->removeInvite($group);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }

    public function deleteGroupOwner(Group $group)
    {
        $userGroup = $this->entityManager->getRepository(UserGroupManager::class)
            ->getOldestManager($group);
        if (!$userGroup) {
            $userGroup = $this->entityManager->getRepository(UserGroup::class)
                ->getOldestMember($group);
        }
        $group->setOwner($userGroup ? $userGroup->getUser() : null);

        $this->entityManager->persist($group);
        $this->entityManager->flush();
    }

    public function addGroupManager(Group $group, User $user)
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

        $this->entityManager->persist($group);
        $this->entityManager->flush();

        return $userGroupManager;
    }

    public function deleteGroupManager(Group $group, User $user)
    {
        if(!$group->isManager($user))
        {
            throw new \RuntimeException('The user is not a group manager of this group');
        }

        $userGroupManager = $this->entityManager->getRepository(UserGroupManager::class)
            ->findOneBy(['group' => $group, 'user' => $user]);

        $this->entityManager->remove($userGroupManager);
        $this->entityManager->flush();
    }
}
