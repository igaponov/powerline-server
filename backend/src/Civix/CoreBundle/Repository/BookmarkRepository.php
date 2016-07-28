<?php

namespace Civix\CoreBundle\Repository;

use Civix\CoreBundle\Entity\Activities;
use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\Bookmark;
use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

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

        if ($type === Activity::TYPE_ALL) {
            $dql = "SELECT COUNT(a) FROM CivixCoreBundle:Bookmark a WHERE a.user = :user";
            $query = $this->_em->createQuery($dql);
            $query->setParameters(array('user' => $user));
            $totalItem = $query->getSingleScalarResult();
            $bookmarks = $this->findBy(array('user' => $user), array('createdAt' => 'DESC'), $itemPerPage, $startRow);

        } else {
            $dql = "SELECT COUNT(a) FROM CivixCoreBundle:Bookmark a WHERE a.type = :type AND a.user = :user";
            $query = $this->_em->createQuery($dql);
            $query->setParameters(array('type' => $type, 'user' => $user));
            $totalItem = $query->getSingleScalarResult();
            $bookmarks = $this->findBy(array('user' => $user, 'type' => $type), array('createdAt' => 'DESC'), $itemPerPage, $startRow);
        }

        $totalPage = ceil($totalItem / $itemPerPage);

        /** @var Bookmark $bookmark */
        foreach($bookmarks as $id => $bookmark) {
            $detail = $this->getItemDetail($bookmark->getType(), $bookmark->getItemId());
            if ($detail == null) {
                $this->delete($bookmark->getId());
                unset($bookmarks[$id]);
                continue;
            }
            $bookmark->setDetail($detail);
        }

        $result = array(
            'page' => $page,
            'total_pages' => $totalPage,
            'total_items' => $totalItem,
            'items' => array_values($bookmarks)
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
        $bookmark = $this->findOneBy(array(
            'type' => $type,
            'itemId' => $itemId,
            'user' => $user
        ));

        if ($bookmark === null) {
            $bookmark = new Bookmark();
            $bookmark->setUser($user);
            $bookmark->setItemId($itemId);
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
     * @author Habibillah <habibillah@gmail.com>
     * @param $itemType
     * @param $itemId
     * @return null|object
     */
    private function getItemDetail($itemType, $itemId)
    {
        $item = null;
        $allowedTypes = self::allowedTypes();
        if (isset($allowedTypes[$itemType]))
            $item = $this->_em->getRepository($allowedTypes[$itemType])->find($itemId);

        return $item;
    }

    /**
     * @return array
     */
    public static function allowedTypes()
    {
        $types = [
            Activity::TYPE_QUESTION => Activities\Question::class,
            Activity::TYPE_PETITION => Activities\Petition::class,
            Activity::TYPE_MICRO_PETITION => Activities\MicroPetition::class,
            Activity::TYPE_LEADER_NEWS => Activities\LeaderNews::class,
            Activity::TYPE_PAYMENT_REQUEST => Activities\PaymentRequest::class,
            Activity::TYPE_CRWODFUNDING_PAYMENT_REQUEST => Activities\CrowdfundingPaymentRequest::class,
            Activity::TYPE_LEADER_EVENT => Activities\LeaderEvent::class,
        ];

        return $types;
    }
}
