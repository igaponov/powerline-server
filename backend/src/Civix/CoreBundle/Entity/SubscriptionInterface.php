<?php
namespace Civix\CoreBundle\Entity;

use Doctrine\Common\Collections\Collection;

interface SubscriptionInterface
{
    /**
     * Add subscriber
     *
     * @param User $subscriber
     * @return UserPetition
     */
    public function addSubscriber(User $subscriber);

    /**
     * Remove subscriber
     *
     * @param User $subscriber
     */
    public function removeSubscriber(User $subscriber);

    /**
     * Get subscribers
     *
     * @return Collection|User[]
     */
    public function getSubscribers();
}