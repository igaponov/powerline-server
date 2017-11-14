<?php

namespace Tests\Civix\ApiBundle\Security\Core;

use Civix\ApiBundle\Security\Core\ApiUserProvider;
use Civix\Component\ContentConverter\ConverterInterface;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Repository\UserRepository;
use Civix\CoreBundle\Service\User\UserManager;
use libphonenumber\PhoneNumber;
use PHPUnit\Framework\TestCase;

class ApiUserProviderTest extends TestCase
{
    public function testLoadUserByPhone()
    {
        $phone = new PhoneNumber();
        $user = new User();
        /** @var \PHPUnit_Framework_MockObject_MockObject|UserRepository $repository */
        $repository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findOneByPhone'])
            ->getMock();
        $repository->expects($this->once())
            ->method('findOneByPhone')
            ->with($phone)
            ->willReturn($user);
        /** @var \PHPUnit_Framework_MockObject_MockObject|UserManager $manager */
        $manager = $this->getMockBuilder(UserManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $converter = $this->createMock(ConverterInterface::class);
        $provider = new ApiUserProvider($repository, $manager, $converter, []);
        $provider->loadUserByPhone($phone);
    }
}
