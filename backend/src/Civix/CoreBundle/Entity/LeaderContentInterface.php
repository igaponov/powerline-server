<?php

namespace Civix\CoreBundle\Entity;

interface LeaderContentInterface
{
    /**
     * @return UserInterface|Group
     */
    public function getGroup();
}