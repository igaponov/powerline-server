<?php
namespace Civix\CoreBundle\Serializer\Type;

use Civix\CoreBundle\Entity\HasAvatarInterface;
use Civix\CoreBundle\Entity\User;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @Vich\Uploadable
 */
class Target implements HasAvatarInterface
{
    private $data;

    /**
     * @Vich\UploadableField(mapping="avatar_image", fileNameProperty="avatarFileName")
     */
    private $avatar;

    private $avatarFileName;

    public function __construct($data = [])
    {
        $this->data = $data;
        if (!empty($data['image'])) {
            $this->avatarFileName = $data['image'];
        }
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * @param mixed $avatar
     *
     * @return $this
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAvatarFileName()
    {
        return $this->avatarFileName;
    }

    /**
     * @param mixed $avatarFileName
     *
     * @return $this
     */
    public function setAvatarFileName($avatarFileName)
    {
        $this->avatarFileName = $avatarFileName;

        return $this;
    }

    public function getDefaultAvatar()
    {
        return User::SOMEONE_AVATAR;
    }
}