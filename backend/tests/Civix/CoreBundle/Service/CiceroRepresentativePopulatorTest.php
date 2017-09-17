<?php

namespace Tests\Civix\CoreBundle\Service;

use Civix\Component\ContentConverter\ConverterInterface;
use Civix\CoreBundle\Entity\CiceroRepresentative;
use Civix\CoreBundle\Entity\District;
use Civix\CoreBundle\Entity\State;
use Civix\CoreBundle\Model\TempFile;
use Civix\CoreBundle\Repository\DistrictRepository;
use Civix\CoreBundle\Repository\StateRepository;
use Civix\CoreBundle\Service\CiceroRepresentativePopulator;
use PHPUnit\Framework\TestCase;

class CiceroRepresentativePopulatorTest extends TestCase
{
    public function testFillRepresentativeByApiObj()
    {
        $state = new State();
        $converter = $this->createMock(ConverterInterface::class);
        $converter->expects($this->once())
            ->method('convert')
            ->with('http://bioguide.congress.gov/bioguide/photo/M/M000639.jpg')
            ->willReturn('conTent');
        $stateRepo = $this->getStateRepositoryMock(['findOneBy']);
        $stateRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => 'DC'])
            ->willReturn($state);
        $districtRepo = $this->getDistrictRepositoryMock(['find']);
        $districtRepo->expects($this->once())
            ->method('find')
            ->with(47);
        $populator = new CiceroRepresentativePopulator($converter, $stateRepo, $districtRepo);
        $representative = new CiceroRepresentative();
        $json = json_decode(file_get_contents(__DIR__.'/../data/representative.json'));
        $populator->fillRepresentativeByApiObj($representative, $json);
        $this->assertSame(33976, $representative->getId());
        $this->assertSame('Robert', $representative->getFirstName());
        $this->assertSame('Menendez', $representative->getLastName());
        $this->assertSame('Senator', $representative->getOfficialTitle());
        $this->assertSame('menendez@senate.gov', $representative->getEmail());
        $this->assertSame('http://www.senate.gov/', $representative->getWebsite());
        $this->assertSame('US', $representative->getCountry());
        $this->assertSame('(202) 224-4744', $representative->getPhone());
        $this->assertSame('(202) 228-2197', $representative->getFax());
        $this->assertSame($state, $representative->getState());
        $this->assertSame('Washington', $representative->getCity());
        $this->assertSame('United States Senate', $representative->getAddressLine1());
        $this->assertSame('528 Hart Senate Office Building', $representative->getAddressLine2());
        $this->assertSame('10', $representative->getAddressLine3());
        $this->assertSame('Democrat', $representative->getParty());
        $this->assertSame('1954-01-01', $representative->getBirthday()->format('Y-m-d'));
        $this->assertSame('2007-01-03 00:00:00', $representative->getStartTerm()->format('Y-m-d H:i:s'));
        $this->assertSame('2019-01-03 00:00:00', $representative->getEndTerm()->format('Y-m-d H:i:s'));
        $this->assertSame('https://www.facebook.com/senatormenendez', $representative->getFacebook());
        $this->assertSame('SenatorMenendez', $representative->getTwitter());
        $this->assertSame('SenatorMenendezNJ', $representative->getYoutube());
        $this->assertSame('M000639', $representative->getBioguide());
        $this->assertInstanceOf(TempFile::class, $representative->getAvatar());
        $this->assertSame(7, $representative->getAvatar()->getClientSize());
        $district = $representative->getDistrict();
        $this->assertNotNull($district);
        $this->assertSame(47, $district->getId());
        $this->assertSame('New Jersey', $district->getLabel());
        $this->assertSame(District::NATIONAL_UPPER, $district->getDistrictType());
        $this->assertNull($representative->getOpenstateId());
        $this->assertNull($representative->getUpdatedAt());
        $this->assertNull($representative->getAvatarFileName());
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|DistrictRepository
     */
    private function getDistrictRepositoryMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(DistrictRepository::class)
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|StateRepository
     */
    private function getStateRepositoryMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(StateRepository::class)
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
