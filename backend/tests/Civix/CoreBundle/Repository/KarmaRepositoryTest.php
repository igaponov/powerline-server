<?php

namespace Tests\Civix\CoreBundle\Repository;

use Civix\CoreBundle\Entity\Karma;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Repository\KarmaRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class KarmaRepositoryTest extends KernelTestCase
{
    public function testFindOneByUserAndType()
    {
        $user = new User();
        $type = Karma::TYPE_REPRESENTATIVE_SCREEN;
        $karma = new Karma($user, $type, 10);
        $repository = $this->getKarmaRepositoryMock(['findOneBy']);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(compact('user', 'type'))
            ->willReturn($karma);
        $this->assertSame($karma, $repository->findOneByUserAndType($user, $type));
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|KarmaRepository
     */
    private function getKarmaRepositoryMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(KarmaRepository::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }
}
