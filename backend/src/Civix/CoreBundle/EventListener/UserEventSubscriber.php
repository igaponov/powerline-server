<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
use Civix\CoreBundle\Service\EmailSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var EmailSender
     */
    private $emailSender;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var int
     */
    private $groupName;

    public function __construct(
        EmailSender $emailSender,
        EntityManagerInterface $entityManager,
        $groupName
    )
    {
        $this->emailSender = $emailSender;
        $this->entityManager = $entityManager;
        $this->groupName = $groupName;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserEvents::REGISTRATION => 'sendInviteFromGroup',
        ];
    }

    public function sendInviteFromGroup(UserEvent $event): void
    {
        $user = $event->getUser();
        $group = $this->entityManager
            ->getRepository(Group::class)
            ->findOneBy(['officialName' => $this->groupName]);
        if ($group) {
            $this->emailSender->sendInviteFromGroup($user->getEmail(), $group);
        }
    }
}