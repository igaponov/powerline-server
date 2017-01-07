<?php

namespace Civix\CoreBundle\Tests\Service;

use Civix\CoreBundle\Service\CiceroApi;
use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\District;
use Civix\CoreBundle\Tests\DataFixtures\ORM as ORM;
use Faker\Factory;

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
        /** @var \PHPUnit_Framework_MockObject_MockObject|CiceroApi $mock */
        $mock = $this->getMockBuilder('Civix\CoreBundle\Service\CiceroApi')
            ->setMethods(array('getNonlegislaveDistricts'))
            ->disableOriginalConstructor()
            ->getMock();
        $ciceroCallsMock = $this->getMockBuilder('Civix\CoreBundle\Service\CiceroCalls')
            ->setMethods(array('getResponse'))
            ->disableOriginalConstructor()
            ->getMock();

        $congressMock = $this->getMockBuilder('Civix\CoreBundle\Service\CongressApi')
            ->setMethods(array('updateRepresentativeProfile'))
            ->disableOriginalConstructor()
            ->getMock();

        $openstatesApi = $this->getMockBuilder('Civix\CoreBundle\Service\OpenstatesApi')
            ->setMethods(array('updateRepresentativeProfile'))
            ->disableOriginalConstructor()
            ->getMock();

        $vichUploader = $this->getMockBuilder('Vich\UploaderBundle\Templating\Helper\UploaderHelper')
            ->setMethods(array('asset'))
            ->disableOriginalConstructor()
            ->getMock();
        $vichUploader->expects($this->any())
            ->method('asset')
            ->will($this->returnValue(null));

        $fileSystem = $this->getMockBuilder('Knp\Bundle\GaufretteBundle\FilesystemMap')
            ->disableOriginalConstructor()
            ->getMock();
        $storage = $this->getMockBuilder('Vich\UploaderBundle\Storage\GaufretteStorage')
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->getMockBuilder('Symfony\Bridge\Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        static::$kernel->getContainer()->set('knp_gaufrette.filesystem_map', $fileSystem);
        static::$kernel->getContainer()->set('vich_uploader.storage.gaufrette', $storage);

        $mock->setCropImage($this->createMock('Civix\CoreBundle\Service\CropImage'));
        $mock->setVichService($vichUploader);
        $mock->setEntityManager(static::$kernel->getContainer()->get('doctrine')->getManager());
        $mock->setCongressApi($congressMock);
        $mock->setOpenstatesApi($openstatesApi);
        $mock->setLogger($logger);

        $ciceroCallsMock->expects($this->once())
           ->method('getResponse')
           ->will($this->returnValue($this->responseCandidates));
        $mock->setCiceroCalls($ciceroCallsMock);
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
            ORM\LoadRepresentativeData::class,
            ORM\LoadCiceroRepresentativeData::class,
        ))->getReferenceRepository();
        $user = $repository->getReference('user_1');
        $faker = Factory::create();
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManager();
        $representativeObj = new Representative($em->merge($user));
        $representativeObj->setCiceroRepresentative($repository->getReference('cicero_representative_jb'));
        $representativeObj->setPrivatePhone($faker->phoneNumber);
        $representativeObj->setPrivateEmail($faker->companyEmail);
        /** @var \PHPUnit_Framework_MockObject_MockObject|CiceroApi $mock */
        $mock = $this->getMockBuilder('Civix\CoreBundle\Service\CiceroApi')
            ->setMethods(['checkLink'])
            ->disableOriginalConstructor()
            ->getMock();

        $ciceroCallsMock = $this->getMockBuilder('Civix\CoreBundle\Service\CiceroCalls')
            ->setMethods(array('getResponse'))
            ->disableOriginalConstructor()
            ->getMock();

        $congressMock = $this->getMockBuilder('Civix\CoreBundle\Service\CongressApi')
            ->setMethods(array('updateRepresentativeProfile'))
            ->disableOriginalConstructor()
            ->getMock();

        $openstatesApi = $this->getMockBuilder('Civix\CoreBundle\Service\OpenstatesApi')
            ->setMethods(array('updateRepresentativeProfile'))
            ->disableOriginalConstructor()
            ->getMock();

        $vichUploader = $this->getMockBuilder('Vich\UploaderBundle\Templating\Helper\UploaderHelper')
            ->setMethods(array('asset', 'updateRepresentativeProfile'))
            ->disableOriginalConstructor()
            ->getMock();
        $vichUploader->expects($this->any())
            ->method('asset')
            ->will($this->returnValue(null));

        $fileSystem = $this->getMockBuilder('Knp\Bundle\GaufretteBundle\FilesystemMap')
            ->disableOriginalConstructor()
            ->getMock();
        $storage = $this->getMockBuilder('Vich\UploaderBundle\Storage\GaufretteStorage')
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->getMockBuilder('Symfony\Bridge\Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        static::$kernel->getContainer()->set('knp_gaufrette.filesystem_map', $fileSystem);
        static::$kernel->getContainer()->set('vich_uploader.storage.gaufrette', $storage);

        $mock->setCropImage($this->createMock('Civix\CoreBundle\Service\CropImage'));
        $mock->setVichService($vichUploader);
        $mock->setEntityManager($em);
        $mock->setCongressApi($congressMock);
        $mock->setOpenstatesApi($openstatesApi);
        $mock->setLogger($logger);

        $ciceroCallsMock->expects($this->any())
                   ->method('getResponse')
                   ->will($this->returnValue($this->responseRepresentative));
        $mock->setCiceroCalls($ciceroCallsMock);
        $mock->expects($this->once())
            ->method('checkLink')
            ->will($this->returnValue(false));

        $result = $mock->updateByRepresentativeInfo($representativeObj);

        $this->assertTrue($result, 'Should return true');
    }
}
