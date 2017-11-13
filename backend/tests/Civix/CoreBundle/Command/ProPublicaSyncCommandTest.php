<?php

namespace Tests\Civix\CoreBundle\Command;

use Civix\CoreBundle\Command\ProPublicaSyncCommand;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Repository\RepresentativeRepository;
use Civix\CoreBundle\Service\ProPublicaRepresentativePopulator;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use GuzzleHttp\Command\Result;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ProPublicaSyncCommandTest extends TestCase
{
    public function testExecute()
    {
        $client = $this->getClientMock(['getNewMembers', 'getMember']);
        $results = new Result(json_decode(file_get_contents(__DIR__.'/../data/propublica_new_members.json'), true));
        $client->expects($this->once())
            ->method('getNewMembers')
            ->with()
            ->willReturn($results);
        $result = ['property' => 'value'];
        $client->expects($this->once())
            ->method('getMember')
            ->with(['id' => 'N000190'])
            ->willReturn(['results' => [$result]]);
        $repository = $this->getRepositoryMock(['findOneBy']);
        $repository->expects($this->at(0))
            ->method('findOneBy')
            ->with(['bioguide' => 'G000585']);
        $representative = new Representative();
        $repository->expects($this->at(1))
            ->method('findOneBy')
            ->with(['bioguide' => 'N000190'])
            ->willReturn($representative);
        $repository->expects($this->at(2))
            ->method('findOneBy')
            ->with(['bioguide' => 'H001078']);
        $populator = $this->getPopulatorMock(['populate']);
        $populator->expects($this->once())
            ->method('populate')
            ->with($representative, $result);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('flush');
        $command = new ProPublicaSyncCommand($client, $populator, $repository, $em);
        $command->run(new ArrayInput([]), new NullOutput());
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|ProPublicaRepresentativePopulator
     */
    private function getPopulatorMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(ProPublicaRepresentativePopulator::class)
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|RepresentativeRepository
     */
    private function getRepositoryMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(RepresentativeRepository::class)
            ->disableOriginalConstructor()
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
