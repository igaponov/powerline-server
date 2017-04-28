<?php
namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\ChangeableAvatarInterface;
use Symfony\Component\EventDispatcher\Event;

class AvatarEvent extends Event
{
    /**
     * @var ChangeableAvatarInterface
     */
    private $entity;

    public function __construct(ChangeableAvatarInterface $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return ChangeableAvatarInterface
     */
    public function getEntity()
    {
        return $this->entity;
    }
}