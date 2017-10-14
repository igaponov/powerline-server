<?php

namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Serializer\Type\Image;

trait AnnouncementSerializableTrait
{
    /**
     * Get image
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api"})
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