<?php

namespace Civix\CoreBundle\Entity;

interface LeaderContentInterface
{
    /**
     * @return LeaderContentRootInterface
     */
    public function getRoot();
}