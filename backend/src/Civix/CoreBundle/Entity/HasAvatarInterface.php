<?php
namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Model\Avatar\DefaultAvatarInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface HasAvatarInterface
{
    /**
     * @param File|UploadedFile $avatar
     * @return $this
     */
    public function setAvatar(File $avatar);

    /**
     * Get avatar.
     *
     * @return string
     */
    public function getAvatar();

    /**
     * Get default avatar.
     *
     * @return DefaultAvatarInterface
     */
    public function getDefaultAvatar(): DefaultAvatarInterface;

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