<?php

namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Serializer\Type\Avatar;
use Civix\CoreBundle\Serializer\Type\ContentRemaining;
use Civix\CoreBundle\Serializer\Type\Image;
use Civix\CoreBundle\Serializer\Type\JoinedStatus;
use Civix\CoreBundle\Serializer\Type\JoinStatus;
use Civix\CoreBundle\Serializer\Type\UserRole;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation as Serializer;

trait GroupSerializableTrait
{
    /**
     * @var int
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"group-list"})
     * @Serializer\Type("integer")
     */
    private $priorityItemCount = 0;

    /**
     * @var int
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"api-full-info", "group-list"})
     * @Serializer\Type("integer")
     */
    private $totalMembers = 0;

    /**
     * @return int
     */
    public function getPriorityItemCount(): int
    {
        return $this->priorityItemCount;
    }

    /**
     * @param int $priorityItemCount
     * @return $this
     */
    public function setPriorityItemCount(int $priorityItemCount): self
    {
        $this->priorityItemCount = $priorityItemCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotalMembers(): int
    {
        return $this->totalMembers;
    }

    /**
     * @param int $totalMembers
     * @return $this
     */
    public function setTotalMembers(int $totalMembers): self
    {
        $this->totalMembers = $totalMembers;

        return $this;
    }

    /**
     * Get avatarSrc.
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups(
     *      {"api-activities", "api-poll","api-groups", "api-info", "api-search",
     *      "api-petitions-list", "api-petitions-info", "api-invites", "api-poll-public"}
     * )
     * @Serializer\Type("Avatar")
     * @Serializer\SerializedName("avatar_file_path")
     * @return Avatar
     */
    public function getAvatarFilePath(): Avatar
    {
        /** @noinspection PhpParamsInspection */
        return new Avatar($this);
    }

    /**
     * Get banner image
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups(
     *      {"api-activities", "api-poll","api-groups", "api-info", "api-search",
     *      "api-petitions-list", "api-petitions-info", "api-invites", "api-poll-public"}
     * )
     * @Serializer\Type("Image")
     * @Serializer\SerializedName("banner")
     * @return Image
     */
    public function getBannerImage(): Image
    {
        return new Image($this, 'banner.file', null, false);
    }

    /**
     * @return JoinedStatus
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-groups", "api-info"})
     * @Serializer\Type("JoinedStatus")
     * @Serializer\SerializedName("joined")
     */
    public function getJoinedStatus(): JoinedStatus
    {
        /** @noinspection PhpParamsInspection */
        return new JoinedStatus($this);
    }

    /**
     * Get Join status.
     *
     * @param User $user
     * @return int
     */
    public function getJoined(User $user): int
    {
        return $user->getGroups()->contains($this) ? 1 : 0;
    }

    /**
     * @return JoinStatus
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-groups", "api-full-info"})
     * @Serializer\Type("JoinStatus")
     * @Serializer\SerializedName("join_status")
     */
    public function getJoinStatus(): JoinStatus
    {
        /** @noinspection PhpParamsInspection */
        return new JoinStatus($this);
    }

    /**
     * @return UserRole
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"user-role", "activity-list"})
     * @Serializer\SerializedName("user_role")
     * @Serializer\Type("UserRole")
     */
    public function getUserRole(): ?UserRole
    {
        /** @var Collection $users */
        $users = $this->users;
        if ($users->count()) {
            return new UserRole($users->first());
        }

        return null;
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"micropetition-config"})
     * @Serializer\Type("ContentRemaining")
     * @return ContentRemaining
     */
    public function getPostsRemaining(): ContentRemaining
    {
        /** @noinspection PhpParamsInspection */
        return new ContentRemaining('post', $this);
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"micropetition-config"})
     * @Serializer\Type("ContentRemaining")
     * @return ContentRemaining
     */
    public function getPetitionsRemaining(): ContentRemaining
    {
        /** @noinspection PhpParamsInspection */
        return new ContentRemaining('petition', $this);
    }

}