<?php

namespace Civix\CoreBundle\Repository;

use Civix\CoreBundle\Entity\LeaderInterface;
use Doctrine\ORM\EntityRepository;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\Group;

/**
 * AnnouncementRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AnnouncementRepository extends EntityRepository
{
    public function getNewQuery(LeaderInterface $owner)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('a')
            ->from($this->getAnnouncementRepositoryName($owner), 'a')
            ->where('a.publishedAt IS NULL')
            ->andWhere("a.{$owner->getType()} = :owner")
            ->setParameter('owner', $owner)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
        ;
    }

    public function getPublishedQuery(LeaderInterface $owner)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('a')
            ->from($this->getAnnouncementRepositoryName($owner), 'a')
            ->where('a.publishedAt IS NOT NULL')
            ->andWhere("a.{$owner->getType()} = :owner")
            ->setParameter('owner', $owner)
            ->orderBy('a.publishedAt', 'DESC')
            ->getQuery()
        ;
    }

    /**
     * @param User $user
     * @param \DateTime $start
     * @return array
     */
    public function findByUser(User $user, \DateTime $start)
    {
        return $this->getByUserQuery($user, $start)->getResult();
    }

    /**
     * @param User $user
     * @param \DateTime $start
     * @return \Doctrine\ORM\Query
     */
    public function getByUserQuery(User $user, \DateTime $start)
    {
        $districtsIds = $user->getDistrictsIds();
        $groupsIds = $user->getGroupsIds();
        $representativeIds = array();

        if (!empty($districtsIds)) {
            $qb = $this->getEntityManager()->createQueryBuilder();
            $representativeIds = $qb->select('r.id')
                ->from('CivixCoreBundle:Representative', 'r')
                ->where($qb->expr()->in('r.district', $districtsIds))
                ->getQuery()
                ->getArrayResult()
            ;

            $representativeIds = array_reduce($representativeIds, function ($ids, $item) {
                $ids[] = $item['id'];

                return $ids;
            }, array());
        }

        $representativeIds = empty($representativeIds) ? array(0) : $representativeIds;
        $groupsIds = empty($groupsIds) ? array(0) : $groupsIds;

        $qb = $this->getEntityManager()->createQueryBuilder();

        return $qb->select('a, r, gr, rs')
            ->from('CivixCoreBundle:Announcement', 'a')
            ->leftJoin('a.group', 'gr')
            ->leftJoin('a.representative', 'r')
            ->leftJoin('r.representativeStorage', 'rs')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->in('a.representative', $representativeIds),
                    $qb->expr()->in('a.group', $groupsIds)
                )
            )
            ->andWhere('a.publishedAt > :start')
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->orderBy('a.publishedAt', 'DESC')
            ->getQuery()
        ;
    }

    public function getAnnouncementCountPerMonth(LeaderInterface $owner)
    {
        $startDate = new \DateTime();
        $calcPeriod = new \DateInterval('P30D');
        $calcPeriod->invert = 1;
        $startDate->add($calcPeriod);

        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('count(a)')
            ->from($this->getAnnouncementRepositoryName($owner), 'a')
            ->where('a.publishedAt >= :start')
            ->andWhere("a.{$owner->getType()} = :owner")
            ->setParameter('start', $startDate)
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @param LeaderInterface $user
     *
     * @return string
     */
    private function getAnnouncementRepositoryName(LeaderInterface $user)
    {
        if ($user instanceof Representative) {
            return 'CivixCoreBundle:Announcement\RepresentativeAnnouncement';
        } elseif ($user instanceof Group) {
            return 'CivixCoreBundle:Announcement\GroupAnnouncement';
        } else {
            throw new \RuntimeException('User object must be an instance of RepresentativeAnnouncement or GroupAnnouncement');
        }
    }
}
