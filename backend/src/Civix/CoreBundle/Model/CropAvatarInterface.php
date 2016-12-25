<?php

namespace Civix\CoreBundle\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface CropAvatarInterface
{
    /**
     * @return UploadedFile
     */
    public function getAvatar();

    /**
     * @param UploadedFile $avatar
     * @return $this
     */
    public function setAvatar(UploadedFile $avatar);

    /**
     * @return string
     */
    public function getAvatarSource();

    /**
     * @param string $avatarSrc
     * @return $this
     */
    public function setAvatarSource($avatarSrc);

    /**
     * @return string
     */
    public function getAvatarFileName();

    /**
     * @param string $avatarName
     * @return $this
     */
    public function setAvatarFileName($avatarName);
}
