<?php

namespace Tests\Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\DiscountCode;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\EventListener\DiscountCodeSubscriber;
use Civix\CoreBundle\Service\Stripe;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class DiscountCodeSubscriberTest extends TestCase
{
    public function testAddDiscountCode(): void
    {
        $user = new User();
        $referralCode = '$REF';
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($discountCode) use ($referralCode, $user) {
                $this->assertInstanceOf(DiscountCode::class, $discountCode);
                /** @var DiscountCode $discountCode */
                $this->assertSame($referralCode, $discountCode->getOriginalCode());
                $this->assertSame($user, $discountCode->getOwner());

                return true;
            }));
        $em->expects($this->once())->method('flush');
        $storage = $this->createMock(TokenStorageInterface::class);
        $stripe = $this->getStripeMock();
        $subscriber = new DiscountCodeSubscriber($em, $storage, $stripe, $referralCode);
        $event = new UserEvent($user);
        $subscriber->addDiscountCode($event);
    }

    /**
     * @param array $methods
     * @return Stripe|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getStripeMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(Stripe::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }
}
