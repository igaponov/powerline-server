<?php

namespace Civix\Component\Cursor\Event;

class ItemsEvent extends CursorEvent
{
    /**
     * @var array
     */
    private $items;

    public function __construct($target, array $items)
    {
        parent::__construct($target);
        $this->items = $items;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array $items
     */
    public function setItems(array $items)
    {
        $this->items = $items;
    }
}