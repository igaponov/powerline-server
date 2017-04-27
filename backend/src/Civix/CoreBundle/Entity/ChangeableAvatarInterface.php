<?php

namespace Civix\CoreBundle\Entity;

interface ChangeableAvatarInterface extends HasAvatarInterface
{
    /**
     * @return string
     */
    public function getAvatarFile();

    /**
     * @param string $avatarFile
     * @return $this
     */
    public function setAvatarFile(string $avatarFile = null);
}