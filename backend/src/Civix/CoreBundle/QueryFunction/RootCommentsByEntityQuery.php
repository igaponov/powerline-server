<?php

namespace Civix\CoreBundle\QueryFunction;

use Civix\Component\Cursor\Event\CursorEvents;
use Civix\Component\Cursor\Event\ItemsEvent;
use Civix\Component\Doctrine\ORM\Cursor;
use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\CommentedInterface;
use Civix\CoreBundle\Entity\Post\Comment;
use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Class RootCommentsByEntityQuery
 * @package Civix\CoreBundle\QueryFunction
 */
class RootCommentsByEntityQuery
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
     * @param CommentedInterface $entity
     * @param User $user
     * @param int $lastId
     * @param int $limit
     * @return Cursor
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    public function __invoke(CommentedInterface $entity, User $user, int $lastId, int $limit): Cursor
    {
        $mapping = $this->em->getClassMetadata(get_class($entity))
            ->getAssociationMapping('comments');
        $class = $mapping['targetEntity'];
        $commentEntityField = 'c.'.$mapping['mappedBy'];
        $query = $this->em->createQueryBuilder()
            ->from($class, 'c')
            ->select('c', 'u', 'r', 'COUNT(ch) AS childrenCount')
            ->leftJoin('c.user', 'u')
            ->leftJoin('c.rates', 'r', Join::WITH, 'r.user = :user')
            ->leftJoin('c.childrenComments', 'ch')
            ->setParameter('user', $user)
            ->where($commentEntityField.' = :entity')
            ->setParameter('entity', $entity)
            ->andWhere('c.parentComment IS NULL')
            ->groupBy('c')
            ->getQuery();

        $cursor = new Cursor($query, $lastId, $limit);
        $cursor->connect(CursorEvents::ITEMS, function (ItemsEvent $event) use ($class, $user) {
            $items = $event->getItems();
            if (empty($items)) {
                return;
            }
            $data = [];
            foreach ($items as $item) {
                /** @var BaseComment $comment */
                $comment = $item[0];
                $comment->setChildCount($item['childrenCount']);
                $data[] = $comment;
            }
            // Multi-step hydration
            $this->getChildCommentsQuery($class, $data, $user)->execute();

            $event->setItems($data);
        });

        return $cursor;
    }

    /**
     * Select 2 child comments for any comment table (where xxx = post|poll|user_petition).
     * Generated SQL:
     *
     * -- connection to root comments for Multi-step hydration
     * SELECT c.id, ch.* FROM xxx_comments c
     * -- join child comments
     * INNER JOIN xxx_comments ch ON ch.pid = c.id AND ch.id IN (
     *      -- select only first 2 comments for each root
     *      SELECT a.id FROM xxx_comments a
     *      WHERE (
     *          SELECT COUNT(*) FROM xxx_comments b
     *          WHERE b.pid = a.pid AND b.id <= a.id
     *      ) <= 2
     * )
     * WHERE c.id in (?)
     *
     * @param string $class
     * @param array $data
     * @param User $user
     * @return Query
     */
    private function getChildCommentsQuery(string $class, array $data, User $user): Query
    {
        $expr = $this->em->getExpressionBuilder();
        $ids = array_map(function (Comment $comment) {
            return $comment->getId();
        }, $data);

        return $this->em->createQueryBuilder()
            ->from($class, 'c')
            ->select('PARTIAL c.{id}', 'ch', 'u', 'r')
            ->leftJoin('c.childrenComments', 'ch', 'WITH',
                $expr->in('ch.id', $this->getAQuery($class)->getDQL())
            )
            ->leftJoin('ch.user', 'u')
            ->leftJoin('ch.rates', 'r', Join::WITH, 'r.user = :user')
            ->setParameter(':user', $user)
            ->where('c.id IN (:ids)')
            ->setParameter(':ids', $ids)
            ->getQuery();
    }

    private function getAQuery(string $class): Query
    {
        return $this->em->createQueryBuilder()
            ->from($class, 'a')
            ->select('a.id')
            ->where("({$this->getBQuery($class)->getDQL()}) <= 2")
            ->getQuery();
    }

    private function getBQuery(string $class): Query
    {
        return $this->em->createQueryBuilder()
            ->from($class, 'b')
            ->select('COUNT(b)')
            ->where('b.parentComment = a.parentComment')
            ->andWhere('b.id <= a.id')
            ->getQuery();
    }
}