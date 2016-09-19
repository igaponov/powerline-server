<?php
namespace Civix\ApiBundle\EventListener;

use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
use Civix\CoreBundle\Service\EmailSender;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var EmailSender
     */
    private $emailSender;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var int
     */
    private $groupName;

    public function __construct(
        EmailSender $emailSender,
        EntityManager $entityManager,
        $groupName
    )
    {
        $this->emailSender = $emailSender;
        $this->entityManager = $entityManager;
        $this->groupName = $groupName;
    }

    public static function getSubscribedEvents()
    {
        return [
            UserEvents::REGISTRATION => 'sendInviteFromGroup',
        ];
    }

    public function sendInviteFromGroup(UserEvent $event)
    {
        $user = $event->getUser();
        $group = $this->entityManager
            ->getRepository('CivixCoreBundle:Group')
            ->findOneBy(['officialName' => $this->groupName]);
        if ($group) {
            $this->emailSender->sendInviteFromGroup($user->getEmail(), $group);
        }
    }
}