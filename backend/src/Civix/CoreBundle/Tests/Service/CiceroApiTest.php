<?php

namespace Civix\CoreBundle\Tests\Service;

use Civix\Component\ContentConverter\ConverterInterface;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Event\AvatarEvent;
use Civix\CoreBundle\Event\AvatarEvents;
use Civix\CoreBundle\Service\CiceroApi;
use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\District;
use Civix\CoreBundle\Service\CiceroCalls;
use Civix\CoreBundle\Service\CongressApi;
use Civix\CoreBundle\Service\OpenstatesApi;
use Civix\CoreBundle\Tests\DataFixtures\ORM as ORM;
use Doctrine\ORM\EntityManager;
use Faker\Factory;
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

    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->responseCandidates = json_decode(file_get_contents(__DIR__.'/TestData/responseCandidates.json'));
        $this->resultCandidatesDistricts = array(19, 533, 10859, 47, 757336, 4652);

        $this->responseRepresentative = json_decode(file_get_contents(__DIR__.'/TestData/representative.json'));

        $this->districtObj = new District();
    }

    protected function tearDown()
    {
        $this->districtObj = null;
        $this->responseCandidates = null;
        $this->resultCandidatesDistricts = null;
        $this->responseRepresentative = null;
        parent::tearDown();
    }

    /**
     * Test method getUserDistrictsFromApi.
     *
     * @group cicero
     */
    public function testUserDistrictsFromApi()
    {
        $this->loadFixtures(array(
            ORM\LoadDistrictData::class,
            ORM\LoadRepresentativeData::class,
        ));
        $ciceroCallsMock = $this->getMockBuilder('Civix\CoreBundle\Service\CiceroCalls')
            ->setMethods(array('getResponse'))
            ->disableOriginalConstructor()
            ->getMock();
        $ciceroCallsMock->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($this->responseCandidates));

        $congressMock = $this->getMockBuilder('Civix\CoreBundle\Service\CongressApi')
            ->setMethods(array('updateRepresentativeProfile'))
            ->disableOriginalConstructor()
            ->getMock();

        $openstatesApi = $this->getMockBuilder('Civix\CoreBundle\Service\OpenstatesApi')
            ->setMethods(array('updateRepresentativeProfile'))
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');
        $converter = $this->createMock(ConverterInterface::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|CiceroApi $mock */
        $mock = $this->getMockBuilder('Civix\CoreBundle\Service\CiceroApi')
            ->setMethods(['getNonlegislaveDistricts'])
            ->setConstructorArgs([
                $ciceroCallsMock,
                static::$kernel->getContainer()->get('doctrine')->getManager(),
                $congressMock,
                $openstatesApi,
                $converter,
                $dispatcher
            ])
            ->getMock();
        $mock->expects($this->once())
            ->method('getNonlegislaveDistricts')
            ->will($this->returnValue([]));

        $districts = $mock->getUserDistrictsFromApi('108 4th St', 'Hoboken', 'NJ');

        $districtsIds = array_map(function (District $district) {
            return $district->getId();
        }, $districts);

        $this->assertEmpty(
            array_diff($districtsIds, $this->resultCandidatesDistricts),
            'Should return empty array of differences'
        );
    }

    /**
     * Test method UpdateByRepresentativeInfo.
     *
     * @group cicero
     */
    public function testUpdateByRepresentativeInfo()
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
        $ciceroCallsMock = $this->getMockBuilder('Civix\CoreBundle\Service\CiceroCalls')
            ->setMethods(array('getResponse'))
            ->disableOriginalConstructor()
            ->getMock();
        /** @var CongressApi $congressMock */
        $congressMock = $this->getMockBuilder('Civix\CoreBundle\Service\CongressApi')
            ->setMethods(array('updateRepresentativeProfile'))
            ->disableOriginalConstructor()
            ->getMock();
        /** @var OpenstatesApi $openstatesApi */
        $openstatesApi = $this->getMockBuilder('Civix\CoreBundle\Service\OpenstatesApi')
            ->setMethods(array('updateRepresentativeProfile'))
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(AvatarEvents::CHANGE, $this->isInstanceOf(AvatarEvent::class));

        $converter = $this->createMock(ConverterInterface::class);

        $ciceroApi = new CiceroApi(
            $ciceroCallsMock,
            $em,
            $congressMock,
            $openstatesApi,
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
