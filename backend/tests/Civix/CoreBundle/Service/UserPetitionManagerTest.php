<?php

namespace Tests\Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Event\UserPetitionEvents;
use Civix\CoreBundle\Event\UserPetitionShareEvent;
use Civix\CoreBundle\Service\UserPetitionManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UserPetitionManagerTest extends TestCase
{
    public function testSharePetition()
    {
        $sharer = new User();
        $signature = (new UserPetition\Signature())->setUser($sharer);
        $petition = (new UserPetition())->addSignature($signature);
        $this->assertNull($sharer->getLastContentSharedAt());
        $em = $this->createMock(EntityManagerInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(UserPetitionEvents::PETITION_SHARE, $this->isInstanceOf(UserPetitionShareEvent::class));
        $manager = new UserPetitionManager($em, $dispatcher);
        $manager->sharePetition($petition, $sharer);
        $this->assertSame(date('Y-m-d H:i'), $sharer->getLastContentSharedAt()->format('Y-m-d H:i'));
    }

    public function testSharePetitionAfterLessThan1HourThrowsException()
    {
        $sharer = (new User())->shareContent();
        $signature = (new UserPetition\Signature())->setUser($sharer);
        $petition = (new UserPetition())->addSignature($signature);
        $em = $this->createMock(EntityManagerInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');
        $manager = new UserPetitionManager($em, $dispatcher);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('User can share a petition only once in 1 hour.');
        $manager->sharePetition($petition, $sharer);
    }

    public function testShareNonSignedPetitionThrowsException()
    {
        $sharer = new User();
        $petition = new UserPetition();
        $em = $this->createMock(EntityManagerInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');
        $manager = new UserPetitionManager($em, $dispatcher);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('User can share only a petition he has signed.');
        $manager->sharePetition($petition, $sharer);
    }
}
