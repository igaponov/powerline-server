<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\GroupEvent;
use Civix\CoreBundle\Event\GroupEvents;
use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

class UploaderSubscriber implements EventSubscriberInterface
{
    /**
     * @var StorageInterface
     */
    private $storage;

    public static function getSubscribedEvents()
    {
        return [
            GroupEvents::BEFORE_AVATAR_DELETE => 'removeGroupAvatar',
            UserEvents::BEFORE_AVATAR_DELETE => 'removeUserAvatar',
        ];
    }

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function removeGroupAvatar(GroupEvent $event)
    {
        $group = $event->getGroup();
        $this->storage->remove($group);
    }

    public function removeUserAvatar(UserEvent $event)
    {
        $user = $event->getUser();
        $this->storage->remove($user);
    }
}