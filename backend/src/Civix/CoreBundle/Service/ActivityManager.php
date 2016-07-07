<?php
namespace Civix\CoreBundle\Service;

use Civix\ApiBundle\Entity\ActivityData;
use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\ActivityRead;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

class ActivityManager
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Bulk update activities (mark as read)
     * 
     * @param ArrayCollection|ActivityData[] $activitiesData
     * @param User $user
     * 
     * @return array|\Civix\CoreBundle\Entity\Activity[]|ArrayCollection
     */
    public function bulkUpdate($activitiesData, User $user)
    {
        $ids = $activitiesData
            ->filter(
                function (ActivityData $activityData) {
                    return $activityData->getRead();
                }
            )
            ->map(function (ActivityData $activityData) {
                return $activityData->getId();
            });
        $repository = $this->em->getRepository(Activity::class);
        $activities = $repository->findWithActivityReadByIdAndUser($ids->toArray(), $user);
        foreach ($activities as $activity) {
            $filter = function (ActivityRead $activityRead) use ($user) {
                return $activityRead->getUser()->getId() == $user->getId();
            };
            if (!$activity->getActivityRead()->filter($filter)->count()) {
                $activityRead = new ActivityRead();
                $activityRead->setUser($user);
                $activityRead->setActivity($activity);
                $this->em->persist($activityRead);
            }
            $activity->setRead(true);
        }
        $this->em->flush();
        
        return $activities;
    }
}