<?php

namespace Civix\CoreBundle\Tests\Service;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\Component\ContentConverter\ConverterInterface;
use Civix\CoreBundle\Entity\District;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Event\AvatarEvent;
use Civix\CoreBundle\Event\AvatarEvents;
use Civix\CoreBundle\Event\CiceroRepresentativeEvent;
use Civix\CoreBundle\Event\CiceroRepresentativeEvents;
use Civix\CoreBundle\Service\CiceroApi;
use Civix\CoreBundle\Service\CiceroCalls;
use Civix\CoreBundle\Tests\DataFixtures\ORM;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CiceroApiTest extends WebTestCase
{
    /**
     * @var Representative
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
            ORM\LoadRepresentativeRelationData::class,
        ))->getReferenceRepository();
        /** @var EntityManager $em */
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManager();
        /** @var Representative $representativeObj */
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
                [CiceroRepresentativeEvents::UPDATE, $this->isInstanceOf(CiceroRepresentativeEvent::class)],
                [AvatarEvents::CHANGE, $this->isInstanceOf(AvatarEvent::class)]
            );

        $converter = $this->createMock(ConverterInterface::class);

        $ciceroApi = new CiceroApi(
            $ciceroCallsMock,
            $em,
            $converter,
            $dispatcher
        );

        $ciceroCallsMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->responseRepresentative));

        $result = $ciceroApi->updateByRepresentativeInfo($representativeObj);
        $this->assertTrue($result, 'Should return true');
        $em->flush();
        $em->refresh($representativeObj);
        $this->assertSame(
            $representativeObj->getCiceroRepresentative()->getId(),
            $this->responseRepresentative->response->results->officials[0]->id
        );
    }
}
