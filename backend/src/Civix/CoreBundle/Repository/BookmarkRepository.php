<?php

namespace Civix\CoreBundle\Repository;

use Civix\CoreBundle\Entity\Bookmark;
use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class BookmarkRepository extends EntityRepository
{
    /**
     * @param string $type
     * @param User $user
     * @param int $page
     * @return array
     */
    public function findByType($type, $user, $page)
    {
        $itemPerPage = 10;
        $startRow = ($page -1) * $itemPerPage;

        if ($type === Bookmark::TYPE_ALL) {
            $dql = "SELECT COUNT(a) FROM CivixCoreBundle:Bookmark a WHERE a.user = :user";
            $query = $this->_em->createQuery($dql);
            $query->setParameters(array('user' => $user));
            $totalItem = $query->getSingleScalarResult();
            $bookmarks = $this->findBy(array('user' => $user), array('createdAt' => 'DESC'),
                $itemPerPage, $startRow);

        } else {
            $dql = "SELECT COUNT(a) FROM CivixCoreBundle:Bookmark a WHERE a.type = :type AND a.user = :user";
            $query = $this->_em->createQuery($dql);
            $query->setParameters(array('type' => $type, 'user' => $user));
            $totalItem = $query->getSingleScalarResult();
            $bookmarks = $this->findBy(array('user' => $user, 'type' => $type),
                array('createdAt' => 'DESC'), $itemPerPage, $startRow);
        }

        $totalPage = ceil($totalItem / $itemPerPage);

        $result = array(
            'page' => $page,
            'total_pages' => $totalPage,
            'total_items' => $totalItem,
            'items' => $bookmarks
        );

        return $result;
    }

    /**
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

    public function delete($id)
    {
        $bookmark = $this->find($id);
        if ($bookmark === null)
            return false;

        $this->_em->remove($bookmark);
        $this->_em->flush();

        return true;
    }
}
