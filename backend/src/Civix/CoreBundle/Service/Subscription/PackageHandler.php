<?php

namespace Civix\CoreBundle\Service\Subscription;

use Civix\CoreBundle\Entity\LeaderInterface;
use Doctrine\ORM\EntityManager;
use Civix\CoreBundle\Model\Subscription\PackageLimitState;
use Civix\CoreBundle\Entity\Group;

class PackageHandler
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var SubscriptionManager
     */
    private $sm;

    public function __construct(EntityManager $em, SubscriptionManager $sm)
    {
        $this->em = $em;
        $this->sm = $sm;
    }

    public function getPackageStateForInvites(Group $user)
    {
        $package = $this->sm->getPackage($user);

        $invitesCount = $this->em->getRepository('CivixCoreBundle:DeferredInvites')
            ->getInvitesCount($user);

        $limitObj = new PackageLimitState();
        $limitObj->setLimitValue($package->getInviteByEmailLimitation());
        $limitObj->setCurrentValue($invitesCount);

        return $limitObj;
    }

    public function getPackageStateForGroupDivisions(Group $user)
    {
        $package = $this->sm->getPackage($user);

        $groupDivisionsCount = $this->em->getRepository('CivixCoreBundle:GroupSection')
            ->getDivisionsCount($user);

        $limitObj = new PackageLimitState();
        $limitObj->setLimitValue($package->getGroupDivisionLimitation());
        $limitObj->setCurrentValue($groupDivisionsCount);

        return $limitObj;
    }

    public function getPackageStateForAnnouncement(LeaderInterface $user)
    {
        $package = $this->sm->getPackage($user);

        $announcementCount = $this->em->getRepository('CivixCoreBundle:Announcement')
            ->getAnnouncementCountPerMonth($user);

        $limitObj = new PackageLimitState();
        $limitObj->setLimitValue($package->getAnnouncementLimitation());
        $limitObj->setCurrentValue($announcementCount);

        return $limitObj;
    }

    public function getPackageStateForMicropetition(Group $user)
    {
        $package = $this->sm->getPackage($user);

        $limitObj = new PackageLimitState();
        $limitObj->setLimitValue($package->getAnnouncementLimitation());
        $limitObj->setCurrentValue($user->getPetitionPerMonth());

        return $limitObj;
    }

    /**
     * @param Group $group
     *
     * @return PackageLimitState
     */
    public function getPackageStateForGroupSize(Group $group)
    {
        $memberCount = $group->getTotalMembers();

        return (new PackageLimitState())
            ->setLimitValue($this->sm->getPackage($group)->getGroupSizeLimitation())
            ->setCurrentValue($memberCount);
    }
}
