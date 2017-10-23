<?php

namespace Civix\CoreBundle\Model;

class FacebookContent
{
    private $userFullName;
    private $username;
    private $userAvatar;
    private $groupName;
    private $groupAvatar;
    private $text;

    public function __construct(
        $userFullName,
        $username,
        $userAvatar,
        $groupName,
        $groupAvatar,
        $text
    ) {
        $this->userFullName = $userFullName;
        $this->username = $username;
        $this->userAvatar = $userAvatar;
        $this->groupName = $groupName;
        $this->groupAvatar = $groupAvatar;
        $this->text = $text;
    }

    /**
     * @return mixed
     */
    public function getUserFullName()
    {
        return $this->userFullName;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return mixed
     */
    public function getUserAvatar()
    {
        return $this->userAvatar;
    }

    /**
     * @return mixed
     */
    public function getGroupName()
    {
        return $this->groupName;
    }

    /**
     * @return mixed
     */
    public function getGroupAvatar()
    {
        return $this->groupAvatar;
    }

    /**
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }
}