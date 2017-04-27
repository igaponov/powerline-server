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
     * @Serializer\Expose()
     * @Serializer\Type("Avatar")
     */
    protected $avatarFileName;

    /**
     * @var UploadedFile $avatar
     *
     * @Assert\File(
     *     maxSize="10M",
     *     mimeTypes={"image/png", "image/jpeg", "image/jpg"}
     * )
     */
    protected $avatar;

    /**
     * Property for avatar uploading
     * Can be an url or a base64-encoded string
     *
     * @var string
     */
    protected $avatarFile;

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

    /**
     * @return string|null
     */
    public function getAvatarFile()
    {
        return $this->avatarFile;
    }

    /**
     * @param string|null $avatarFile
     * @return $this|HasAvatarInterface
     */
    public function setAvatarFile(string $avatarFile = null): HasAvatarInterface
    {
        $this->avatarFile = $avatarFile;

        return $this;
    }
}