<?php

namespace Civix\CoreBundle\Entity;

interface LeaderContentInterface
{
    /**
     * @return LeaderContentRootInterface
     */
    public function getRoot();

    /**
     * @param LeaderContentRootInterface $root
     * @return mixed
     */
    public function setRoot(LeaderContentRootInterface $root);
}