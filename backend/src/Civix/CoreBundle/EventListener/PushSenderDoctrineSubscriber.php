<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Service\PushTask;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

class PushSenderDoctrineSubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            Events::postPersist,
        ];
    }

    /**
     * @var PushTask
     */
    private $pushTask;

    public function __construct(PushTask $pushTask)
    {
        $this->pushTask = $pushTask;
    }

    public function postPersist(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();

        switch (true) {
            case $entity instanceof SocialActivity:
                $this->pushTask->addToQueue('sendSocialActivity', [$entity->getId()]);
                break;
        }
    }
}