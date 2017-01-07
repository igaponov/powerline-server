<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\AvatarEvent;
use Civix\CoreBundle\Event\AvatarEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Storage\StorageInterface;

class UploaderSubscriber implements EventSubscriberInterface
{
    /**
     * @var PropertyMappingFactory
     */
    private $factory;
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

    public function __construct(PropertyMappingFactory $factory, StorageInterface $storage)
    {
        $this->factory = $factory;
        $this->storage = $storage;
    }

    public function removeAvatar(AvatarEvent $event)
    {
        $entity = $event->getEntity();
        $mapping = $this->factory->fromField($entity, 'avatar');
        $this->storage->remove($entity, $mapping);
    }
}