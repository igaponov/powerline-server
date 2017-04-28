<?php

namespace Civix\CoreBundle\Entity;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

trait HasAvatarTrait
{
    /**
     * @var string
     *
     * @ORM\Column(name="avatar_file_name", type="string", length=255, nullable=true)
     */
    protected $avatarFileName;

    /**
     * @var UploadedFile $avatar
     *
     * @Assert\File(
     *     maxSize="10M",
     *     mimeTypes={"image/png", "image/jpeg", "image/jpg"},
     *     groups={"avatar"}
     * )
     */
    protected $avatar;

    /**
     * @param string $avatarFileName
     * @return $this
     */
    public function setAvatarFileName($avatarFileName)
    {
        $this->avatarFileName = $avatarFileName;

        return $this;
    }

    /**
     * @return string
     */
    public function getAvatarFileName()
    {
        return $this->avatarFileName;
    }

    /**
     * Set avatar.
     *
     * @param File $avatar
     * @return $this
     */
    public function setAvatar(File $avatar)
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * Get avatar.
     *
     * @return UploadedFile
     */
    public function getAvatar()
    {
        return $this->avatar;
    }
}