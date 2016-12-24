<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\GroupEvent;
use Civix\CoreBundle\Event\GroupEvents;
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
}