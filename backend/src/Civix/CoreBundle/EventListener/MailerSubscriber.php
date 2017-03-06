<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\DiscountCodeEvent;
use Civix\CoreBundle\Event\DiscountCodeEvents;
use Civix\CoreBundle\Event\GroupEvent;
use Civix\CoreBundle\Event\GroupEvents;
use Civix\CoreBundle\Service\EmailSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MailerSubscriber implements EventSubscriberInterface
{
    /**
     * @var EmailSender
     */
    private $sender;

    public static function getSubscribedEvents()
    {
        return [
            GroupEvents::REGISTERED => 'sendRegistrationSuccessGroup',
            DiscountCodeEvents::CREATE => 'sendRewardCode',
        ];
    }

    public function __construct(EmailSender $sender)
    {
        $this->sender = $sender;
    }
    
    public function sendRegistrationSuccessGroup(GroupEvent $event)
    {
        $this->sender->sendRegistrationSuccessGroup($event->getGroup());
    }

    public function sendRewardCode(DiscountCodeEvent $event)
    {
        $this->sender->sendRewardCodeEmail($event->getUser(), $event->getDiscountCode());
    }
}