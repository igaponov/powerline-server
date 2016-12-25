<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\AvatarEvent;
use Civix\CoreBundle\Event\AvatarEvents;
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
            AvatarEvents::BEFORE_DELETE => 'removeAvatar',
        ];
    }

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function removeAvatar(AvatarEvent $event)
    {
        $entity = $event->getEntity();
        $this->storage->remove($entity);
    }
}