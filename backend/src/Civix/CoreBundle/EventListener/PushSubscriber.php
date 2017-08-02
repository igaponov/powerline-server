<?php

namespace Civix\CoreBundle\EventListener;

use Civix\Component\Notification\Event\ErrorEvent;
use Civix\Component\Notification\Event\PushMessageEvent;
use Civix\Component\Notification\Event\PushMessageEvents;
use Civix\Component\Notification\Exception\AWSNotificationException;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\QueryFunction\CountPriorityActivities;
use Doctrine\ORM\EntityManager;
use Imgix\UrlBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PushSubscriber implements EventSubscriberInterface
{
    const IMAGE_PATH = 'avatars';
    const IMAGE_WIDTH = 320;
    const IMAGE_HEIGHT = 400;
    const IMAGE_LINK = '/bundles/civixfront/img/logo_320x320.jpg';

    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var UrlBuilder
     */
    private $urlBuilder;
    /**
     * @var string
     */
    private $hostname;

    public static function getSubscribedEvents(): array
    {
        return [
            PushMessageEvents::PRE_SEND => [
                ['setBadge'],
                ['handleImage'],
            ],
            PushMessageEvents::ERROR => 'onError',
        ];
    }

    public function __construct(EntityManager $em, UrlBuilder $urlBuilder, string $hostname)
    {
        $this->em = $em;
        $this->urlBuilder = $urlBuilder;
        $this->hostname = $hostname;
    }

    public function setBadge(PushMessageEvent $event): void
    {
        $message = $event->getMessage();
        $recipient = $message->getRecipient();
        if ($recipient instanceof User) {
            $queryBuilder = new CountPriorityActivities($this->em);
            $badge = $queryBuilder($recipient, new \DateTime('-30 days'));
            $message->setBadge($badge);
        }
    }

    public function onError(ErrorEvent $event): void
    {
        $exception = $event->getException();
        if ($exception instanceof AWSNotificationException) {
            $this->em->remove($exception->getEndpoint());
            $this->em->flush();
        }
    }

    public function handleImage(PushMessageEvent $event)
    {
        $message = $event->getMessage();
        if (!$message->getImage()) {
            $image = 'https://'.$this->hostname.self::IMAGE_LINK;
        } else {
            $image = $this->urlBuilder->createURL(
                self::IMAGE_PATH.'/'.$message->getImage(),
                ['dpr' => 0.75, 'w' => self::IMAGE_WIDTH, 'h' => self::IMAGE_HEIGHT]
            );
        }
        $message->setImage($image);
    }
}