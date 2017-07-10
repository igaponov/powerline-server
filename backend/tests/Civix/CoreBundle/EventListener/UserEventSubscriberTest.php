<?php

namespace Tests\Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\EventListener\UserEventSubscriber;
use Civix\CoreBundle\Repository\GroupRepository;
use Civix\CoreBundle\Service\EmailSender;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UserEventSubscriberTest extends TestCase
{
    public function testSendInviteFromGroup(): void
    {
        $user = new User();
        $user->setEmail('test@mail.com');
        $group = new Group();
        $groupName = 'Test Group';
        /** @var EmailSender|\PHPUnit_Framework_MockObject_MockObject $sender */
        $sender = $this->getMockBuilder(EmailSender::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendInviteFromGroup'])
            ->getMock();
        $sender->expects($this->once())
            ->method('sendInviteFromGroup')
            ->with($user->getEmail(), $group);
        $repository = $this->getMockBuilder(GroupRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['officialName' => $groupName])
            ->willReturn($group);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Group::class)
            ->willReturn($repository);
        $subscriber = new UserEventSubscriber($sender, $em, $groupName);
        $event = new UserEvent($user);
        $subscriber->sendInviteFromGroup($event);
    }

    public function testSendInviteFromNotFoundGroup(): void
    {
        $user = new User();
        $groupName = 'Test Group';
        /** @var EmailSender|\PHPUnit_Framework_MockObject_MockObject $sender */
        $sender = $this->getMockBuilder(EmailSender::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendInviteFromGroup'])
            ->getMock();
        $sender->expects($this->never())
            ->method('sendInviteFromGroup');
        $repository = $this->getMockBuilder(GroupRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['officialName' => $groupName]);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Group::class)
            ->willReturn($repository);
        $subscriber = new UserEventSubscriber($sender, $em, $groupName);
        $event = new UserEvent($user);
        $subscriber->sendInviteFromGroup($event);
    }
}
