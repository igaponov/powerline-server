<?php
namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\HasAvatarInterface;
use Symfony\Component\EventDispatcher\Event;

class AvatarEvent extends Event
{
    /**
     * @var HasAvatarInterface
     */
    private $entity;

    public function __construct(HasAvatarInterface $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return HasAvatarInterface
     */
    public function getEntity()
    {
        return $this->entity;
    }
}