<?php

namespace Civix\Component\Notification\Model;

interface RecipientInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getUsername();
}