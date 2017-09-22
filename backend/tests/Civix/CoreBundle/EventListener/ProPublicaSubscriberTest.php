<?php

namespace Tests\Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\CiceroRepresentative;
use Civix\CoreBundle\Event\CiceroRepresentativeEvent;
use Civix\CoreBundle\EventListener\ProPublicaSubscriber;
use Civix\CoreBundle\Service\ProPublicaRepresentativePopulator;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use GuzzleHttp\Command\Result;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProPublicaSubscriberTest extends TestCase
{
    public function testGetInfo()
    {
        $representative = new CiceroRepresentative();
        $representative->setBioguide('M000639');
        $client = $this->getClientMock(['getMember']);
        $result = new Result(['results' => [['id' => 'K0001']]]);
        $client->expects($this->once())
            ->method('getMember')
            ->with(['id' => $representative->getBioguide()])
            ->willReturn($result);
        $populator = $this->getProPublicaRepresentativePopulatorMock(['populate']);
        $populator->expects($this->once())
            ->method('populate')
            ->with($representative, $result['results'][0]);
        $logger = $this->createMock(LoggerInterface::class);
        $subscriber = new ProPublicaSubscriber($client, $populator, $logger);
        $event = new CiceroRepresentativeEvent($representative);
        $subscriber->getInfo($event);
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|ProPublicaRepresentativePopulator
     */
    private function getProPublicaRepresentativePopulatorMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(ProPublicaRepresentativePopulator::class)
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|GuzzleClient
     */
    private function getClientMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(GuzzleClient::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }
}
