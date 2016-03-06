<?php
namespace Civix\ApiBundle\Tests\EventListener;

use Civix\ApiBundle\EventListener\UserPrePersistListener;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Service\PhoneNumberNormalizer;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Psr\Log\NullLogger;

class UserPrePersistListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testPrePersist()
    {
        $phone = '+1 650 253 0000';
        $country = 'US';
        $expected = '+16502530000';
        $normalizer = $this->getNormalizerMock();
        $normalizer->expects($this->once())
            ->method('normalize')
            ->with($phone, $country)
            ->will($this->returnValue($expected));
        $manager = $this->getManagerMock();
        $listener = new UserPrePersistListener($normalizer, new NullLogger());
        $user = new User();
        $user->setPhone($phone);
        $user->setCountry($country);
        $event = new LifecycleEventArgs($user, $manager);
        $listener->prePersist($event);
        $this->assertSame($expected, $user->getPhone());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PhoneNumberNormalizer
     */
    private function getNormalizerMock()
    {
        $normalizer = $this->getMockBuilder(PhoneNumberNormalizer::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $normalizer;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    private function getManagerMock()
    {
        $manager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $manager;
    }
}