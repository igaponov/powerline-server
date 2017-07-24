<?php

namespace Civix\Component\Cursor\Event;

use Symfony\Component\EventDispatcher\Event;

abstract class CursorEvent extends Event
{
    private $target;

    public function __construct($target)
    {
        $this->target = $target;
    }

    /**
     * @return mixed
     */
    public function getTarget()
    {
        return $this->target;
    }
}