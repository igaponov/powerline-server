<?php

namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Serializer\Type\Image;
use JMS\Serializer\Annotation as Serializer;

trait PostSerializableTrait
{
    /**
     * @Serializer\Groups({"api-petitions-info"})
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("share_picture")
     * @Serializer\Type("Image")
     *
     * @return Image
     */
    public function getSharePicture(): Image
    {
        $entity = $this->isBoosted() ? $this->getGroup() : $this->getUser();

        return new Image($entity, 'avatar');
    }

    /**
     * Get facebook thumbnail image
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"Default", "post", "activity-list"})
     * @Serializer\Type("Image")
     * @Serializer\SerializedName("facebook_thumbnail")
     *
     * @return Image
     */
    public function getFacebookThumbnailImage(): Image
    {
        return new Image($this, 'facebookThumbnail.file', null, false);
    }

    /**
     * Get image
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"Default", "post", "activity-list"})
     * @Serializer\Type("Image")
     * @Serializer\SerializedName("image")
     *
     * @return Image
     */
    public function getImageFile(): Image
    {
        return new Image($this, 'image.file', null, false);
    }
}