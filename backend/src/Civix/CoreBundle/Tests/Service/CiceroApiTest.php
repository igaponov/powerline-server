<?php

namespace Civix\CoreBundle\Tests\Service;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\Component\ContentConverter\ConverterInterface;
use Civix\CoreBundle\Entity\District;
use Civix\CoreBundle\Entity\UserRepresentative;
use Civix\CoreBundle\Event\AvatarEvent;
use Civix\CoreBundle\Event\AvatarEvents;
use Civix\CoreBundle\Event\RepresentativeEvent;
use Civix\CoreBundle\Event\RepresentativeEvents;
use Civix\CoreBundle\Service\CiceroApi;
use Civix\CoreBundle\Service\CiceroCalls;
use Civix\CoreBundle\Service\CiceroRepresentativePopulator;
use Civix\CoreBundle\Tests\DataFixtures\ORM;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CiceroApiTest extends WebTestCase
{
    /**
     * @var UserRepresentative
     */
    private $districtObj;
    private $responseCandidates;
    private $resultCandidatesDistricts;
    private $responseRepresentative;

    public function setUp(): void
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->responseCandidates = json_decode(file_get_contents(__DIR__.'/TestData/responseCandidates.json'));
        $this->resultCandidatesDistricts = array(19, 533, 10859, 47, 757336, 4652);

        $this->responseRepresentative = json_decode(file_get_contents(__DIR__.'/TestData/representative.json'));

        $this->districtObj = new District();
    }

    protected function tearDown(): void
    {
        $this->districtObj = null;
        $this->responseCandidates = null;
        $this->resultCandidatesDistricts = null;
        $this->responseRepresentative = null;
        parent::tearDown();
    }

    /**
     * Test method UpdateByRepresentativeInfo.
     *
     * @group cicero
     */
    public function testUpdateByRepresentativeInfo(): void
    {
        $repository = $this->loadFixtures(array(
            ORM\LoadUserRepresentativeRelationData::class,
        ))->getReferenceRepository();
        /** @var EntityManager $em */
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManager();
        /** @var UserRepresentative $representativeObj */
        $representativeObj = $repository->getReference('representative_jb');
        /** @var CiceroCalls|\PHPUnit_Framework_MockObject_MockObject $ciceroCallsMock */
        $ciceroCallsMock = $this->getMockBuilder(CiceroCalls::class)
            ->setMethods(['getResponse'])
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [AvatarEvents::CHANGE, $this->isInstanceOf(AvatarEvent::class)],
                [RepresentativeEvents::UPDATE, $this->isInstanceOf(RepresentativeEvent::class)]
            );

        $converter = $this->createMock(ConverterInterface::class);
        $populator = new CiceroRepresentativePopulator(
            $converter,
            $this->getContainer()->get('civix_core.state_repository'),
            $this->getContainer()->get('civix_core.district_repository')
        );

        $ciceroApi = new CiceroApi(
            $ciceroCallsMock,
            $em,
            $dispatcher,
            $populator
        );

        $ciceroCallsMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->responseRepresentative));

        $result = $ciceroApi->updateByRepresentativeInfo($representativeObj);
        $this->assertTrue($result, 'Should return true');
        $em->flush();
        $em->refresh($representativeObj);
        $this->assertSame(
            $representativeObj->getRepresentative()->getCiceroId(),
            $this->responseRepresentative->response->results->officials[0]->id
        );
    }
}
