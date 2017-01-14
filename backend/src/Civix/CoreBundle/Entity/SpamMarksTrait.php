<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

trait SpamMarksTrait
{
    /**
     * @var ArrayCollection|User[]
     *
     * @ORM\ManyToMany(targetEntity="Civix\CoreBundle\Entity\User", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    protected $spamMarks;

    /**
     * Mark post as spam
     *
     * @param User $user
     * @return $this
     */
    public function markAsSpam(User $user)
    {
        if (!$this->spamMarks->contains($user)) {
            $this->spamMarks->add($user);
        }

        return $this;
    }

    /**
     * Mark post as not spam
     *
     * @param User $user
     * @return $this
     */
    public function markAsNotSpam(User $user)
    {
        $this->spamMarks->removeElement($user);

        return $this;
    }

    /**
     * @return User[]|ArrayCollection
     */
    public function getSpamMarks()
    {
        return $this->spamMarks;
    }
}