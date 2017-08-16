<?php

namespace Civix\Component\Cursor;

interface CursorInterface extends \IteratorAggregate
{

    public function getNextCursor();

    public function connect(string $eventName, callable $listener, int $priority = 0);
}