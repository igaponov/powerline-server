<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Service\PushTask;
use Civix\CoreBundle\Service\QueueTaskInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

/**
 * Class PushSenderDoctrineSubscriber
 * collects entities on postPersist Doctrine's event
 * and add it to external queue on postFlush,
 * because postPersist event
 * is called before UnitOfWork::commit() method
 * and the entity can be not saved to db completely.
 */
class PushSenderDoctrineSubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            Events::postPersist,
            Events::postFlush,
        ];
    }

    /**
     * @var PushTask
     */
    private $pushTask;

    /**
     * @var \SplQueue
     */
    private $pushQueue;

    public function __construct(QueueTaskInterface $pushTask)
    {
        $this->pushTask = $pushTask;
        $this->pushQueue = new \SplQueue();
        $this->pushQueue->setIteratorMode(\SplQueue::IT_MODE_DELETE);
    }

    public function postPersist(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();

        switch (true) {
            case $entity instanceof SocialActivity:
                $this->pushQueue->enqueue($entity);
                break;
        }
    }

    public function postFlush()
    {
        foreach ($this->pushQueue as $entity) {
            switch (true) {
                case $entity instanceof SocialActivity:
                    $this->pushTask->addToQueue('sendSocialActivity', [$entity->getId()]);
                    break;
            }
        }
    }
}