<?php

namespace Civix\CoreBundle\Entity;

interface LeaderContentInterface
{
    /**
     * @return UserInterface
     */
    public function getGroup();
}