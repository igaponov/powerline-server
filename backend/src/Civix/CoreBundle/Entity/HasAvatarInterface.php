<?php
namespace Civix\CoreBundle\Entity;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface HasAvatarInterface
{
    /**
     * @param UploadedFile $avatar
     * @return $this
     */
    public function setAvatar(UploadedFile $avatar);

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

    /**
     * @param string $avatarFileName
     * @return $this
     */
    public function setAvatarFileName($avatarFileName);

    /**
     * @return string
     */
    public function getAvatarFileName();
}