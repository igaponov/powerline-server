<?php

namespace Civix\CoreBundle\Repository;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\GroupSection;
use Civix\CoreBundle\Entity\Karma;
use Civix\CoreBundle\Entity\LeaderContentInterface;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\SubscriptionInterface;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Model\Group\GroupSectionInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Service\PushSender;
use libphonenumber\PhoneNumber;

/**
 * UserRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends EntityRepository
{
    /**
     * Get all users with filling address profile.
     *
     * @return User[]
     */
    public function getAllUsersWithAddressProfile(): array
    {
        return $this->createQueryBuilder('u')
                ->where('u.state IS NOT NULL')
                ->andWhere('u.city IS NOT NULL')
                ->andWhere('u.address1 IS NOT NULL')
                ->getQuery()
                ->getResult();
    }

    public function getQueryUserOrderedById(): Query
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC')
            ->getQuery();
    }

    public function removeUser(User $user): void
    {
        $this->getEntityManager()
            ->createQueryBuilder()
            ->update('CivixCoreBundle:Activity a')
            ->set('a.user', 'NULL')
            ->where('a.user = :userId')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->execute();

        $this->getEntityManager()
            ->createQueryBuilder()
            ->update('CivixCoreBundle:Poll\Answer a')
            ->set('a.user', 'NULL')
            ->where('a.user = :userId')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->execute();

        $this->getEntityManager()->getConnection()
                ->delete('users_groups', ['user_id' => $user->getId()]);

        $this->getEntityManager()
            ->createQueryBuilder()
            ->delete('CivixCoreBundle:User u')
            ->where('u.id = :userId')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->execute();
    }

    public function getUserByUsername(User $user, $username): array
    {
        $followingIds = $user->getFollowingIds();

        return $this->createQueryBuilder('u')
                ->where('u.username = :username')
                ->andWhere('u.id <> :user')
                ->andWhere('u.id NOT IN (:ids)')
                ->setParameter('username', $username)
                ->setParameter('user', $user->getId())
                ->setParameter('ids', empty($followingIds) ? array(0) : $followingIds)
                ->getQuery()
                ->getResult();
    }

    public function getUsersByDistrictForPush($district, $type, $startId = 0, $limit = null): array
    {
        /** @var $query \Doctrine\ORM\QueryBuilder */
        $query = $this->createQueryBuilder('u');
        $expr = $query->expr();

        $query
            ->innerJoin('u.districts', 'dist');

        $this->setCommonFilterForPush($query, $expr);

        switch ($type) {
            case PushSender::TYPE_PUSH_ANNOUNCEMENT:
                $query->andWhere('u.isNotifMessages = true');
                break;
            case PushSender::TYPE_PUSH_ACTIVITY:
                $query->andWhere('u.isNotifQuestions = true');
                break;
            case PushSender::TYPE_PUSH_NEWS:
                $query->andWhere('u.isNotifDiscussions = true');
                break;
        }

        $query
            ->andWhere('dist.id = :districtId')
            ->andWhere('u.id > :startId')
            ->setParameter('districtId', $district)
            ->setParameter('startId', $startId)
            ->orderBy('u.id', 'ASC');

        if ($limit) {
            $query->setMaxResults($limit);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * @param $startId
     * @param $limit
     * @return array
     *
     * @deprecated
     */
    public function getAllUsersForPush($startId, $limit): array
    {
        /** @var $query \Doctrine\ORM\QueryBuilder */
        $query = $this->createQueryBuilder('u');
        $expr = $query->expr();

        $this->setCommonFilterForPush($query, $expr);

        return $query
                ->andWhere('u.isNotifQuestions = true')
                ->andWhere('u.id > :startId')
                ->orderBy('u.id', 'ASC')
                ->setMaxResults($limit)
                ->setParameter('startId', $startId)
                ->getQuery()
                ->getResult();
    }

    /**
     * @param User $user
     * @return User[]
     */
    public function getUsersByFollowingForPush(User $user): array
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $expr = $query->expr();

        $query->select('u, g')
            ->from('CivixCoreBundle:User', 'u')
            ->leftJoin('u.groups', 'g')
            ->leftJoin('u.following', 'f');

        $this->setCommonFilterForPush($query, $expr);

        return $query
            ->andWhere('f.user = :user')
            ->andWhere('u.isNotifMicroFollowing = true')
            ->andWhere('f.notifying = true')
            ->andWhere('f.status = :status')
            ->andWhere('u.followedDoNotDisturbTill < :date')
            ->andWhere('f.doNotDisturbTill < :date')
            ->setParameter(':user', $user)
            ->setParameter(':status', UserFollow::STATUS_ACTIVE)
            ->setParameter(':date', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    public function getUsersByGroupForPush($groupId, $type, $startId = 0, $limit = null): array
    {
        /** @var $query \Doctrine\ORM\QueryBuilder */
        $query = $this->createQueryBuilder('u');
        $expr = $query->expr();

        $query->innerJoin('u.groups', 'gr');
        $this->setCommonFilterForPush($query, $expr);

        switch ($type) {
            case PushSender::TYPE_PUSH_ANNOUNCEMENT:
                $query->andWhere('u.isNotifMessages = true');
                break;
            case PushSender::TYPE_PUSH_PETITION:
                $query->andWhere('u.isNotifMicroGroup = true');
                break;
            case PushSender::TYPE_PUSH_ACTIVITY:
                $query->andWhere('u.isNotifQuestions = true');
                break;
        }

        $query
                ->andWhere('gr.group = :groupId')
                ->andWhere('u.id > :startId')
                ->orderBy('u.id', 'ASC')
                ->setParameter('groupId', $groupId)
                ->setParameter('startId', $startId);

        if ($limit) {
            $query->setMaxResults($limit);
        }

        return $query->getQuery()->getResult();
    }

    public function getUsersByGroupAndFollowingForPush(Group $group, User $user): IterableResult
    {
        /** @var $query \Doctrine\ORM\QueryBuilder */
        $query = $this->createQueryBuilder('u');
        $expr = $query->expr();

        $query
            ->distinct()
            ->innerJoin('u.groups', 'gr')
            ->leftJoin('u.following', 'f');
        $this->setCommonFilterForPush($query, $expr);

        $query
            ->andWhere('gr.group = :group')
            ->setParameter('group', $group)
            ->andWhere('u.isNotifMicroFollowing = true')
            ->andWhere('u.isNotifMicroGroup = true')
            ->andWhere('u.followedDoNotDisturbTill < :date')
            ->andWhere('f.user = :user')
            ->andWhere('f.notifying = true')
            ->andWhere('f.status = :status')
            ->andWhere('f.doNotDisturbTill < :date')
            ->setParameter(':user', $user)
            ->setParameter(':status', UserFollow::STATUS_ACTIVE)
            ->setParameter(':date', new \DateTime());

        return $query->getQuery()->iterate();
    }

    public function getUsersBySectionsForPush($sectionsIds, $type, $startId = 0, $limit = null): array
    {
        /** @var $query \Doctrine\ORM\QueryBuilder */
        $query = $this->createQueryBuilder('u');
        $expr = $query->expr();

        $query->innerJoin('u.groupSections', 'gs');
        $this->setCommonFilterForPush($query, $expr);
        switch ($type) {
            case PushSender::TYPE_PUSH_ANNOUNCEMENT:
                $query->andWhere('u.isNotifMessages = true');
                break;
            case PushSender::TYPE_PUSH_PETITION:
                $query->andWhere('u.isNotifMicroGroup = true');
                break;
            case PushSender::TYPE_PUSH_ACTIVITY:
                $query->andWhere('u.isNotifQuestions = true');
                break;
        }

        $query
                ->andWhere('gs.id in (:sections)')
                ->andWhere('u.id > :startId')
                ->orderBy('u.id', 'ASC')
                ->setParameter('sections', $sectionsIds)
                ->setParameter('startId', $startId);

        if ($limit) {
            $query->setMaxResults($limit);
        }

        return $query->getQuery()->getResult();
    }

    public function getUserForPush($userId): ?User
    {
        /** @var $query \Doctrine\ORM\QueryBuilder */
        $query = $this->createQueryBuilder('u');
        $expr = $query->expr();

        $this->setCommonFilterForPush($query, $expr);

        return $query
                ->andWhere('u.isNotifMessages = true')
                ->andWhere('u.id = :userId')
                ->setParameter('userId', $userId)
                ->getQuery()
                ->getOneOrNullResult();
    }

    public function findByQueryForFollow($query, User $user): array
    {
        $qb = $this->createQueryBuilder('u');
        $this->addQueryFilter($qb, $query);

        return $qb
            ->andWhere('u.id != :user_id')
            ->setParameter('user_id', $user->getId())
            ->getQuery()->getResult()
        ;
    }

    public function getUserByFacebookId($facebookId): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.facebookId = :facebookId')
            ->setParameter('facebookId', $facebookId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getFacebookUsers(array $facebookIds, $excludeIds): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.facebookId IN (:facebookIds)')
            ->andWhere('u.id NOT IN (:following)')
            ->setParameter('facebookIds', empty($facebookIds) ? array(-1) : $facebookIds)
            ->setParameter('following', empty($excludeIds) ? array(0) : $excludeIds)
            ->getQuery()
            ->getResult();
    }


    /** @noinspection MoreThanThreeArgumentsInspection
     * @param $params
     * @param array|null $orderBy
     * @param null $limit
     * @param null $offset
     * @param User|null $user
     * @return array
     * @deprecated
     */
    public function findByParams($params, array $orderBy = null, $limit = null, $offset = null, User $user = null): array
    {
        $qb = $this->createQueryBuilder('u');
        if (!empty($params['query'])) {
            $this->addQueryFilter($qb, $params['query']);
        }

        if ($user && isset($params['unfollowing']) && $params['unfollowing']) {
            $this->addUnfollowingFilter($qb, $user);
        }

        foreach ($orderBy as $sort => $order) {
            $qb->addOrderBy($sort, $order);
        }

        return $qb
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()->getResult()
        ;
    }

    public function getUsersByGroup($groupId, $page, $limit): array
    {
        $query = $this->createQueryBuilder('u');

        $query->innerJoin('u.groups', 'gr');

        $query
            ->orderBy('u.id', 'ASC')
            ->where('gr.group = :groupId')
            ->setParameter('groupId', $groupId);

        $query->setMaxResults($limit);
        $query->setFirstResult(($page - 1) * $limit);

        return $query->getQuery()->getResult();
    }

    private function addUnfollowingFilter(QueryBuilder $qb, User $user): QueryBuilder
    {
        $excludedIds = array_merge(array($user->getId()), $user->getFollowingIds());
        $qb->andWhere('u.id NOT IN (:excluded_ids)')->setParameter('excluded_ids', $excludedIds);

        return $qb;
    }

    private function addQueryFilter(QueryBuilder $qb, $query): QueryBuilder
    {
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->like('u.username', $qb->expr()->literal('%'.$query.'%')),
            $qb->expr()->like('u.firstName', $qb->expr()->literal('%'.$query.'%')),
            $qb->expr()->like('u.lastName', $qb->expr()->literal('%'.$query.'%'))
        ));

        return $qb;
    }

    private function setCommonFilterForPush(QueryBuilder $qb, Query\Expr $expr): QueryBuilder
    {
        $qb
            ->andWhere(
                $expr->orX(
                    'u.doNotDisturb = false',
                    $expr->andX(
                        'u.doNotDisturb = true',
                        'u.isNotifScheduled = true',
                        $expr->not(
                            $expr->between(
                                ':currentTime',
                                'u.scheduledFrom',
                                'u.scheduledTo'
                            )
                        )
                    )
                ))
            ->setParameter('currentTime', date('H:i:s'));

        return $qb;
    }

    /** @noinspection MoreThanThreeArgumentsInspection
     * @param array $emailHashes
     * @param array $phoneHashes
     * @param $exclude
     * @param $page
     * @param $limit
     * @return array
     * @throws \InvalidArgumentException
     * @deprecated
     */
    public function getUsersByEmailAndPhoneHashes(
        array $emailHashes, 
        array $phoneHashes, 
        $exclude, 
        $page, 
        $limit
    ): array {
        $query = $this->createQueryBuilder('u');

        $query->orderBy('u.id', 'ASC');
        if ($exclude) {
            $query->where($query->expr()->neq('u.id', $exclude));
        }
        $or = $query->expr()->orX();
        if ($emailHashes) {
            $or->add($query->expr()->in('u.emailHash', $emailHashes));
        }
        if ($phoneHashes) {
            $or->add($query->expr()->in('u.phoneHash', $phoneHashes));
        }
        $query->andWhere($or);

        $query->setMaxResults($limit);
        $query->setFirstResult(($page - 1) * $limit);

        return $query->getQuery()->getResult();
    }

    /**
     * Returns partial user entities
     * [{id, first name, last name, email}, ...]
     *
     * This method uses Partial Objects,
     * that leads to potentially more fragile code
     *
     * @see http://doctrine-orm.readthedocs.io/projects/doctrine-orm/en/latest/reference/partial-objects.html
     * 
     * @param $groupId
     * @return User[]
     */
    public function getUsersEmailsByGroup($groupId): array
    {
        $query = $this->createQueryBuilder('u');
        $query
            ->select('partial u.{id,firstName,lastName,email}')
            ->innerJoin('u.groups', 'gr')
            ->orderBy('u.id', 'ASC')
            ->where('gr.group = :groupId')
            ->setParameter('groupId', $groupId);
        
        return $query->getQuery()->getResult(Query::HYDRATE_OBJECT);
    }

    public function findForInviteByGroupWithUsernameOrEmail(Group $group, $userNames): IterableResult
    {
        $qb = $this->createQueryBuilder('u');
        $query = $qb
            ->distinct()
            ->leftJoin('u.managedGroups', 'mg', Query\Expr\Join::WITH, 'mg.group = :group')
            ->leftJoin('u.ownedGroups', 'og', Query\Expr\Join::WITH, 'og = :group')
            ->leftJoin('u.groups', 'ug', Query\Expr\Join::WITH, 'ug.group = :group')
            ->leftJoin('u.invites', 'i', Query\Expr\Join::WITH, 'i = :group')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->in('u.username', ':userNames'),
                    $qb->expr()->in('u.email', ':userNames')
                )
            )
            ->andWhere('mg.id IS NULL')
            ->andWhere('og.id IS NULL')
            ->andWhere('ug.id IS NULL')
            ->andWhere('i.id IS NULL')
            ->setParameter(':group', $group)
            ->setParameter(':userNames', $userNames)
            ->getQuery();

        return $query->iterate();
    }

    public function findForInviteByPostUpvotes(Group $group, Post $post): IterableResult
    {
        $qb = $this->createQueryBuilder('u');
        $query = $qb
            ->distinct()
            ->leftJoin(Post\Vote::class, 'v', 'WITH', 'v.user = u')
            ->leftJoin('u.managedGroups', 'mg', Query\Expr\Join::WITH, 'mg.group = :group')
            ->leftJoin('u.ownedGroups', 'og', Query\Expr\Join::WITH, 'og = :group')
            ->leftJoin('u.groups', 'ug', Query\Expr\Join::WITH, 'ug.group = :group')
            ->leftJoin('u.invites', 'i', Query\Expr\Join::WITH, 'i = :group')
            ->where('v.post = :post')
            ->andWhere('v.option = :option')
            ->andWhere('mg.id IS NULL')
            ->andWhere('og.id IS NULL')
            ->andWhere('ug.id IS NULL')
            ->andWhere('i.id IS NULL')
            ->setParameter(':group', $group)
            ->setParameter(':post', $post)
            ->setParameter(':option', Post\Vote::OPTION_UPVOTE)
            ->getQuery();

        return $query->iterate();
    }

    public function findForInviteByUserPetitionSignatures(Group $group, UserPetition $petition): IterableResult
    {
        $qb = $this->createQueryBuilder('u');
        $query = $qb
            ->distinct()
            ->leftJoin(UserPetition\Signature::class, 's', 'WITH', 's.user = u')
            ->leftJoin('u.managedGroups', 'mg', Query\Expr\Join::WITH, 'mg.group = :group')
            ->leftJoin('u.ownedGroups', 'og', Query\Expr\Join::WITH, 'og = :group')
            ->leftJoin('u.groups', 'ug', Query\Expr\Join::WITH, 'ug.group = :group')
            ->leftJoin('u.invites', 'i', Query\Expr\Join::WITH, 'i = :group')
            ->where('s.petition = :petition')
            ->andWhere('mg.id IS NULL')
            ->andWhere('og.id IS NULL')
            ->andWhere('ug.id IS NULL')
            ->andWhere('i.id IS NULL')
            ->setParameter(':group', $group)
            ->setParameter(':petition', $petition)
            ->getQuery();

        return $query->iterate();
    }

    /**
     * @return User[]
     */
    public function findWithDuplicateEmails(): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin(User::class, 'u2', Query\Expr\Join::WITH, 'u2.email = u.email')
            ->where('u.id <> u2.id')
            ->getQuery()
            ->getResult();
    }

    public function getSubscribersIterator(SubscriptionInterface $subscription): IterableResult
    {
        $qb = $this->createQueryBuilder('u')
            ->distinct();
        if ($subscription instanceof UserPetition) {
            $qb->leftJoin('u.petitionSubscriptions', 's');
        } elseif ($subscription instanceof Post) {
            $qb->leftJoin('u.postSubscriptions', 's');
        } elseif ($subscription instanceof Question) {
            $qb->leftJoin('u.pollSubscriptions', 's');
        } else {
            throw new \RuntimeException(
                sprintf('Wrong subscription type: %s', get_class($subscription))
            );
        }
        return $qb->where('s = :subscription')
            ->setParameter(':subscription', $subscription)
            ->getQuery()
            ->iterate();
    }

    public function getFindByGroupSectionQuery(GroupSection $section): Query
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.groupSections', 's')
            ->where('s = :section')
            ->setParameter(':section', $section)
            ->getQuery();
    }

    /**
     * @param LeaderContentInterface $content
     * @param $type
     * @param int $startId
     * @param null $limit
     * @return User[]
     */
    public function getUsersByGroupForLeaderContentPush(LeaderContentInterface $content, $type, $startId = 0, $limit = null): array
    {
        if (($content instanceof GroupSectionInterface) && $content->getGroupSections()->count() > 0) {
            return $this->getUsersBySectionsForPush($content->getGroupSectionIds(), $type, $startId, $limit);
        }

        return $this->getUsersByGroupForPush($content->getRoot()->getId(), $type, $startId, $limit);
    }

    /**
     * Return group's members (only references instead of entities)
     * @param Group $group
     * @param User[] $exclude Exclude users from result
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     */
    public function findAllMembersByGroup(Group $group, User ...$exclude): array
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();
        $userQuery = $conn->createQueryBuilder()
            ->select('ug.user_id')
            ->from('users_groups', 'ug')
            ->where('ug.group_id = :group');
        $managerQuery = $conn->createQueryBuilder()
            ->select('mg.user_id')
            ->from('users_groups_managers', 'mg')
            ->where('mg.group_id = :group');
        $stmt = $conn->executeQuery(
            sprintf('%s UNION %s', $userQuery->getSQL(), $managerQuery->getSQL()),
            [':group' => $group->getId()]
        );
        $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $owner = $group->getOwner();
        if ($owner && !in_array($owner->getId(), $ids, false)) {
            $ids[] = $owner->getId();
        }
        foreach ($exclude as $item) {
            if (($key = array_search($item->getId(), $ids, false)) !== false) {
                unset($ids[$key]);
            }
        }

        return array_map(function ($id) use ($em) {
            return $em->getReference($this->getClassName(), $id);
        }, $ids);
    }

    public function getUserKarma(User $user): int
    {
        return (int)$this->getEntityManager()->createQueryBuilder()
            ->select('SUM(k.points)')
            ->from(Karma::class, 'k')
            ->where('k.user = :user')
            ->setParameter(':user', $user)
            ->getQuery()->getSingleScalarResult();
    }

    public function findWithFollowerById($id, User $follower): ?User
    {
        return $this->createQueryBuilder('u')
            ->addSelect('f')
            ->leftJoin('u.followers', 'f', 'WITH', 'f.follower = :follower')
            ->setParameter(':follower', $follower)
            ->where('u.id = :id')
            ->setParameter(':id', $id)
            ->getQuery()->getOneOrNullResult();
    }

    public function findByUsernameOrEmailOrPhone(array $criteria): array
    {
        $qb = $this->createQueryBuilder('u');
        $expr = $this->getEntityManager()->getExpressionBuilder();
        foreach ($criteria as $property => $value) {
            $key = ':'.$property;
            $qb->orWhere($expr->eq('u.'.$property, $key))
                ->setParameter($key, $value);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Filter given users by a group and a content creator.
     * Uses PARTIAL select because all users are already fully hydrated
     *
     * @param Group $group
     * @param User $creator
     * @param User[] ...$users
     *
     * @return User[]
     */
    public function filterByGroupAndFollower(Group $group, User $creator, User ...$users): array
    {
        if (($key = array_search($creator, $users, true)) !== false) {
            unset($users[$key]);
        }

        return $this->createQueryBuilder('u')
            ->select('PARTIAL u.{id}')
            ->leftJoin('u.groups', 'ug')
            ->andWhere('ug.group = :group')
            ->setParameter('group', $group)
            ->andWhere('ug.status = :status')
            ->setParameter(':status', UserGroup::STATUS_ACTIVE)
            ->andWhere('u.id IN (:users)')
            ->setParameter(':users', $users)
            ->getQuery()
            ->getResult();
    }

    public function findOneByPhone(PhoneNumber $phone)
    {
        return $this->findOneBy(['phone' => $phone]);
    }

    public function existsByUsernameOrEmailOrPhone($username, $email, $phone)
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u)')
            ->where('u.username = :username')
            ->setParameter(':username', $username)
            ->orWhere('u.email = :email')
            ->setParameter(':email', $email)
            ->orWhere('u.phone = :phone')
            ->setParameter(':phone', $phone)
            ->getQuery()->getSingleScalarResult() > 0;
    }
}
