<?php
namespace Civix\CoreBundle\Entity;

interface UserMentionableInterface
{
    /**
     * @param User $user
     * @return $this
     */
    public function addMentionedUser(User $user);

    /**
     * @return User[]
     */
    public function getMentionedUsers();
}