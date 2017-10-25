<?php

namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Serializer\Type\Image;
use DateTime;
use Doctrine\Common\Collections\Collection;

trait UserPetitionSerializableTrait
{
    /**
     * Virtual property for old endpoint
     *
     * @return mixed
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("petition_body_html")
     * @Serializer\Type("string")
     *
     * @internal
     */
    public function getPetitionBodyHtml(): string
    {
        return $this->htmlBody;
    }

    /**
     * Virtual property for old endpoint
     *
     * @return mixed
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("petition_body")
     * @Serializer\Type("string")
     *
     * @internal
     */
    public function getPetitionBody(): string
    {
        return $this->body;
    }

    /**
     * Virtual property for old endpoint
     *
     * @return mixed
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("expire_at")
     * @Serializer\Type("DateTime")
     *
     * @internal
     */
    public function getExpireAt(): DateTime
    {
        return new DateTime('+1 year');
    }

    /**
     * Virtual property for old endpoint
     *
     * @return mixed
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("user_expire_interval")
     * @Serializer\Type("integer")
     *
     * @internal
     */
    public function getUserExpireInterval(): int
    {
        return 0;
    }

    /**
     * Virtual property for old endpoint
     *
     * @return mixed
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("type")
     * @Serializer\Type("string")
     *
     * @internal
     */
    public function getType(): string
    {
        return 'long petition';
    }

    /**
     * Virtual property for old endpoint
     *
     * @return mixed
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("link")
     * @Serializer\Type("string")
     *
     * @internal
     */
    public function getLink(): string
    {
        return '';
    }

    /**
     * Virtual property for old endpoint
     *
     * @return mixed
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("publish_status")
     * @Serializer\Type("integer")
     *
     * @internal
     */
    public function getPublishStatus(): int
    {
        return (int)$this->boosted;
    }

    /**
     * Virtual property for old endpoint
     * @return Collection
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Type("array<Civix\CoreBundle\Entity\UserPetition\Signature>")
     * @Serializer\Groups({"api-petitions-answers"})
     *
     * @internal
     */
    public function getAnswers(): Collection
    {
        return $this->signatures;
    }

    /**
     * @return bool
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Type("boolean")
     * @Serializer\Groups({"activity-list"})
     */
    public function isSubscribed(): bool
    {
        /** @var Collection $subscribers */
        $subscribers = $this->subscribers;

        return !$subscribers->isEmpty();
    }

    /**
     * @return int
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("group_id")
     * @Serializer\Type("integer")
     */
    public function getGroupId(): ?int
    {
        /** @var Group $group */
        $group = $this->getGroup();

        return $group ? $group->getId() : null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("share_picture")
     * @Serializer\Type("Image")
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
     * @Serializer\Groups({"Default", "petition"})
     * @Serializer\Type("Image")
     * @Serializer\SerializedName("facebook_thumbnail")
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
     * @Serializer\Groups({"Default", "petition"})
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