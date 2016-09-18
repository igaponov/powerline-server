<?php

namespace Civix\CoreBundle\Repository;

use Civix\CoreBundle\Entity\Activities;
use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\Bookmark;
use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class BookmarkRepository extends EntityRepository
{
    /**
     * @author Habibillah <habibillah@gmail.com>
     * @param string $type
     * @param User $user
     * @param int $page
     * @return array
     */
    public function findByType($type, $user, $page)
    {
        $itemPerPage = 10;
        $startRow = ($page -1) * $itemPerPage;

        $qb = $this->createQueryBuilder('b')
            ->select('b', 'act', 'q', 'up', 'p', 'ps', 'pos', 'qs')
            ->leftJoin('b.item', 'act')
            ->leftJoin('act.question', 'q')
            ->leftJoin('act.petition', 'up')
            ->leftJoin('act.post', 'p')
            ->leftJoin('up.subscribers', 'ps', Join::WITH, 'ps = :user')
            ->leftJoin('p.subscribers', 'pos', Join::WITH, 'pos = :user')
            ->leftJoin('q.subscribers', 'qs', Join::WITH, 'qs = :user')
            ->where('b.user = :user')
            ->setParameter(':user', $user)
            ->orderBy('b.createdAt', 'DESC')
            ->setFirstResult($startRow)
            ->setMaxResults($itemPerPage);
        $cqb = $this->createQueryBuilder('b')
            ->select('COUNT(b)')
            ->where('b.user = :user')
            ->setParameter(':user', $user);

        if ($type !== Activity::TYPE_ALL) {
            $qb->andWhere('b.type = :type')
                ->setParameter(':type', $type);
            $cqb->andWhere('b.type = :type')
                ->setParameter(':type', $type);
        }

        $totalItem = $cqb->getQuery()->getSingleScalarResult();

        $result = array(
            'page' => $page,
            'total_pages' => ceil($totalItem / $itemPerPage),
            'total_items' => $totalItem,
            'items' => $qb->getQuery()->getResult()
        );

        return $result;
    }

    /**
     * @author Habibillah <habibillah@gmail.com>
     * @param $type
     * @param User $user
     * @param $itemId
     * @return null | Bookmark
     */
    public function save($type, $user, $itemId)
    {
        $item = $this->getEntityManager()->getReference(Activity::class, $itemId);

        $bookmark = $this->findOneBy(array(
            'type' => $type,
            'item' => $item,
            'user' => $user
        ));

        if ($bookmark === null) {
            $bookmark = new Bookmark();
            $bookmark->setUser($user);
            $bookmark->setItem($item);
            $bookmark->setType($type);
            $bookmark->setCreatedAt(date_create());

            $this->_em->persist($bookmark);
            $this->_em->flush();
        }

        return $bookmark;
    }

    /**
     * @author Habibillah <habibillah@gmail.com>
     * @param $id
     * @return bool
     */
    public function delete($id)
    {
        $bookmark = $this->find($id);
        if ($bookmark === null)
            return false;

        $this->_em->remove($bookmark);
        $this->_em->flush();

        return true;
    }

    /**
     * @return array
     */
    public static function allowedTypes()
    {
        $types = [
            Activity::TYPE_QUESTION => Activities\Question::class,
            Activity::TYPE_PETITION => Activities\Petition::class,
            Activity::TYPE_USER_PETITION => Activities\UserPetition::class,
            Activity::TYPE_LEADER_NEWS => Activities\LeaderNews::class,
            Activity::TYPE_PAYMENT_REQUEST => Activities\PaymentRequest::class,
            Activity::TYPE_CROWDFUNDING_PAYMENT_REQUEST => Activities\CrowdfundingPaymentRequest::class,
            Activity::TYPE_LEADER_EVENT => Activities\LeaderEvent::class,
            Activity::TYPE_POST => Activities\Post::class,
        ];

        return $types;
    }
}
