<?php

namespace Tests\Civix\CoreBundle\Service;

use Civix\Component\ContentConverter\ConverterInterface;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\District;
use Civix\CoreBundle\Entity\State;
use Civix\CoreBundle\Repository\DistrictRepository;
use Civix\CoreBundle\Repository\StateRepository;
use Civix\CoreBundle\Service\RepresentativeImporter;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

class RepresentativeImporterTest extends TestCase
{
    public function testImport()
    {
        $path = __DIR__.'/../data/representatives.csv';
        $content = file_get_contents(__FILE__);
        $stateNJ = new State();
        $stateMO = new State();
        $district = new District();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(2))
            ->method('persist')
            ->withConsecutive(
                [$this->callback(
                    function (Representative $representative) use ($stateNJ, $district) {
                        $this->assertSame('Robert', $representative->getFirstName());
                        $this->assertSame('Menendez', $representative->getLastName());
                        $this->assertSame('Senator', $representative->getOfficialTitle());
                        $this->assertSame('(973) 645-3030', $representative->getPhone());
                        $this->assertSame('(973) 645-0502', $representative->getFax());
                        $this->assertSame('menendez@senate.gov', $representative->getEmail());
                        $this->assertSame('http://menendez.senate.gov/', $representative->getWebsite());
                        $this->assertSame('US', $representative->getCountry());
                        $this->assertSame('Newark', $representative->getCity());
                        $this->assertSame('One Gateway Center', $representative->getAddressLine1());
                        $this->assertSame('Suite 1100', $representative->getAddressLine2());
                        $this->assertSame('', $representative->getAddressLine3());
                        $this->assertSame('Democrat', $representative->getParty());
                        $this->assertSame('1954-01-01', $representative->getBirthday()->format('Y-m-d'));
                        $this->assertSame('2006-01-18', $representative->getStartTerm()->format('Y-m-d'));
                        $this->assertSame('2008-02-03', $representative->getEndTerm()->format('Y-m-d'));
                        $this->assertSame('http://menendez.senate.gov/contact/', $representative->getContactForm());
                        $this->assertSame(7., $representative->getMissedVotes());
                        $this->assertSame(19., $representative->getVotesWithParty());
                        $this->assertSame('https://www.facebook.com/senatormenendez', $representative->getFacebook());
                        $this->assertSame('SenatorMenendezNJ', $representative->getYoutube());
                        $this->assertSame('SenatorMenendez', $representative->getTwitter());
                        $this->assertSame('M000639', $representative->getBioguide());
                        $this->assertSame($stateNJ, $representative->getState());
                        $this->assertSame($district, $representative->getDistrict());
                        $this->assertNull($representative->getAvatar());

                        return true;
                    })
                ],
                [$this->callback(
                    function (Representative $representative) use ($stateMO, $content) {
                        $this->assertSame('Jason', $representative->getFirstName());
                        $this->assertSame('Holsman', $representative->getLastName());
                        $this->assertSame('Minority Caucus Chair', $representative->getOfficialTitle());
                        $this->assertSame('(573) 751-6607', $representative->getPhone());
                        $this->assertSame('', $representative->getFax());
                        $this->assertSame('sweb@senate.mo.gov', $representative->getEmail());
                        $this->assertSame('http://www.senate.mo.gov/', $representative->getWebsite());
                        $this->assertSame('US', $representative->getCountry());
                        $this->assertSame('Jefferson City', $representative->getCity());
                        $this->assertSame('MO Senate', $representative->getAddressLine1());
                        $this->assertSame('201 West Capitol Avenue', $representative->getAddressLine2());
                        $this->assertSame('Room 421', $representative->getAddressLine3());
                        $this->assertSame('Democrat', $representative->getParty());
                        $this->assertSame('1976-03-25', $representative->getBirthday()->format('Y-m-d'));
                        $this->assertSame('2013-01-09', $representative->getStartTerm()->format('Y-m-d'));
                        $this->assertSame('2021-01-11', $representative->getEndTerm()->format('Y-m-d'));
                        $this->assertSame('', $representative->getContactForm());
                        $this->assertSame(0., $representative->getMissedVotes());
                        $this->assertSame(1., $representative->getVotesWithParty());
                        $this->assertSame('', $representative->getFacebook());
                        $this->assertSame('', $representative->getYoutube());
                        $this->assertSame('jasonholsman', $representative->getTwitter());
                        $this->assertSame('151728', $representative->getBioguide());
                        $this->assertSame($stateMO, $representative->getState());
                        $this->assertNull($representative->getDistrict());
                        $this->assertSame(mb_strlen($content), $representative->getAvatar()->getClientSize());

                        return true;
                    })
                ]
            );
        $em->expects($this->once())
            ->method('flush');
        /** @var \PHPUnit_Framework_MockObject_MockObject|StateRepository $stateRepository */
        $stateRepository = $this->getMockBuilder(StateRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $stateRepository->expects($this->exactly(2))
            ->method('findOneBy')
            ->withConsecutive(
                [['code' => 'NJ']],
                [['code' => 'MO']]
            )
            ->willReturnOnConsecutiveCalls($stateNJ, $stateMO);
        /** @var \PHPUnit_Framework_MockObject_MockObject|DistrictRepository $districtRepository */
        $districtRepository = $this->getMockBuilder(DistrictRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $districtRepository->expects($this->exactly(2))
            ->method('findOneBy')
            ->withConsecutive(
                [['label' => 'NJ']],
                [['label' => 'Missouri State Senate district 7']]
            )
            ->willReturnOnConsecutiveCalls($district, null);
        $converter = $this->createMock(ConverterInterface::class);
        $converter->expects($this->exactly(2))
            ->method('convert')
            ->withConsecutive(['58878062770eb.jpeg'], ['58d4c2af81e66.jpg'])
            ->willReturnOnConsecutiveCalls(null, $content);
        $importer = new RepresentativeImporter($em, $stateRepository, $districtRepository, $converter);
        $importer->import(new File($path));
    }
}
