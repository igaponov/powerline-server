<?php

namespace Civix\CoreBundle\Repository;

use Civix\CoreBundle\Entity\Bookmark;
use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

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

        /** @var Bookmark $bookmark */
        foreach($bookmarks as $bookmark) {
            $detail = $this->getItemDetail($bookmark->getType(), $bookmark->getItemId());
            $bookmark->setDetail($detail);
        }

        $result = array(
            'page' => $page,
            'total_pages' => $totalPage,
            'total_items' => $totalItem,
            'items' => $bookmarks
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
        switch ($itemType) {
            case Bookmark::TYPE_POST:
                $item = $this->_em
                    ->getRepository('CivixCoreBundle:Content\Post')
                    ->find($itemId);
                break;
            case Bookmark::TYPE_POLL:
                $item = $this->_em
                    ->getRepository('CivixCoreBundle:Poll\Question')
                    ->find($itemId);
                break;
            case Bookmark::TYPE_POLL_ANSWER:
                $item = $this->_em
                    ->getRepository('CivixCoreBundle:Poll\Answer')
                    ->find($itemId);
                break;
            case Bookmark::TYPE_POLL_COMMENT:
                $item = $this->_em
                    ->getRepository('CivixCoreBundle:Poll\Comment')
                    ->find($itemId);
                break;
            case Bookmark::TYPE_PETITION:
                $item = $this->_em
                    ->getRepository('CivixCoreBundle:Micropetitions\Petition')
                    ->find($itemId);
                break;
            case Bookmark::TYPE_PETITION_ANSWER:
                $item = $this->_em
                    ->getRepository('CivixCoreBundle:Micropetitions\Answer')
                    ->find($itemId);
                break;
            case Bookmark::TYPE_PETITION_COMMENT:
                $item = $this->_em
                    ->getRepository('CivixCoreBundle:Micropetitions\Comment')
                    ->find($itemId);
                break;
        }

        return $item;
    }
}
