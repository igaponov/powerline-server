<?php

namespace Tests\Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\EventListener\MailerSubscriber;
use Civix\CoreBundle\Service\EmailSender;
use PHPUnit\Framework\TestCase;

class MailerSubscriberTest extends TestCase
{
    public function testSendRegistrationEmail(): void
    {
        $user = new User();
        /** @var EmailSender|\PHPUnit_Framework_MockObject_MockObject $sender */
        $sender = $this->getMockBuilder(EmailSender::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendRegistrationEmail'])
            ->getMock();
        $sender->expects($this->once())
            ->method('sendRegistrationEmail')
            ->with($user);
        $subscriber = new MailerSubscriber($sender);
        $event = new UserEvent($user);
        $subscriber->sendRegistrationEmail($event);
    }
}
