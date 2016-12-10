<?php
namespace Civix\CoreBundle\Entity;

interface HasAvatarInterface
{
    /**
     * Get avatar.
     *
     * @return string
     */
    public function getAvatar();

    /**
     * Get default avatar.
     *
     * @return string
     */
    public function getDefaultAvatar();
}