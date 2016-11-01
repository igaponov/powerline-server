<?php
namespace Civix\CoreBundle\Entity;

trait UserMentionableTrait
{
    /**
     * @var array Users, mentioned in body to send notification
     */
    protected $mentionedUsers = [];

    /**
     * @param User $user
     * @return $this
     */
    public function addMentionedUser(User $user)
    {
        $this->mentionedUsers[] = $user;

        return $this;
    }

    /**
     * @return User[]
     */
    public function getMentionedUsers()
    {
        return $this->mentionedUsers;
    }
}