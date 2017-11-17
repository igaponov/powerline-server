<?php

namespace Civix\Component\Doctrine\ORM;

use Civix\Component\Cursor\CursorInterface;
use Civix\Component\Cursor\Event\CursorEvents;
use Civix\Component\Cursor\Event\ItemsEvent;
use Civix\Component\Doctrine\ORM\Query\WhereWalker;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Cursor implements CursorInterface
{
    /**
     * @var QueryBuilder
     */
    private $qb;
    /**
     * @var int
     */
    private $cursor;
    /**
     * @var int
     */
    private $nextCursor;
    /**
     * @var int
     */
    private $limit;
    /**
     * @var \ArrayIterator
     */
    private $iterator;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(QueryBuilder $qb, int $cursor, int $limit, ?EventDispatcherInterface $dispatcher = null)
    {
        $this->qb = $qb;
        $this->cursor = $cursor;
        $this->limit = $limit;
        if ($dispatcher) {
            $this->dispatcher = $dispatcher;
        } else {
            $this->dispatcher = new EventDispatcher();
        }
    }

    public function getIterator(): \ArrayIterator
    {
        if (!$this->iterator) {
            $aliases = $this->qb->getRootAliases();
            $query = $this->qb->getQuery();
            $this->addCustomTreeWalker($query, WhereWalker::class);
            $query->setMaxResults($this->limit + 1)
                ->setHint(WhereWalker::HINT_CURSOR_FILTER_COLUMNS, [$aliases[0].'.id'])
                ->setHint(WhereWalker::HINT_CURSOR_FILTER_VALUE, $this->cursor);
            $event = new ItemsEvent($query, $query->getResult());
            $this->dispatcher->dispatch(CursorEvents::ITEMS, $event);
            $items = $event->getItems();
            if ($this->limit < count($items)) {
                $last = array_pop($items);
                $this->nextCursor = $last->getId();
            }
            $this->iterator = new \ArrayIterator($items);
        }

        return $this->iterator;
    }

    public function getNextCursor()
    {
        if (!$this->iterator) {
            $this->getIterator();
        }

        return $this->nextCursor;
    }

    public function connect(string $eventName, callable $listener, int $priority = 0)
    {
        $this->dispatcher->addListener($eventName, $listener, $priority);
    }

    private function addCustomTreeWalker(Query $query, $walker)
    {
        $customTreeWalkers = $query->getHint(Query::HINT_CUSTOM_TREE_WALKERS);
        if ($customTreeWalkers !== false && is_array($customTreeWalkers)) {
            $customTreeWalkers = array_merge($customTreeWalkers, array($walker));
        } else {
            $customTreeWalkers = array($walker);
        }
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $customTreeWalkers);
    }
}