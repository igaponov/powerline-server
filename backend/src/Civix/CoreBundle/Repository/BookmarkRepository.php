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
     * @return QueryBuilder
     */
    public function findByType($type, $user)
    {
        if ($type === Bookmark::TYPE_ALL) {
            $dql = "SELECT a FROM CivixCoreBundle:Bookmark a WHERE a.user = :user ORDER by a.createdAt DESC";
            $query = $this->_em->createQuery($dql);
            $query->setParameters(array(
                'user' => $user
            ));
        } else {
            $dql = "SELECT a FROM CivixCoreBundle:Bookmark a WHERE a.type = :type AND a.user = :user ORDER by a.createdAt DESC";
            $query = $this->_em->createQuery($dql);
            $query->setParameters(array(
                'type' => $type,
                'user' => $user
            ));
        }

        return $query;
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

        if (is_null($bookmark)) {
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

    public function remove($id)
    {
        $bookmark = $this->getEntityManager()
            ->getRepository(Bookmark::class)
            ->find($id);
        if (is_null($bookmark))
            return false;

        $this->getEntityManager()->remove($bookmark);
        $this->getEntityManager()->flush();

        return true;
    }
}
