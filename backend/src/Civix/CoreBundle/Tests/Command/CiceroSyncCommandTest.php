<?php

namespace Civix\CoreBundle\Tests\Command;

use Civix\Component\ContentConverter\ConverterInterface;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Service\CiceroApi;
use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Service\CongressApi;
use Civix\CoreBundle\Service\OpenstatesApi;
use Civix\CoreBundle\Tests\Mock\Service\CiceroCalls;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Civix\CoreBundle\Command\CiceroSyncCommand;
use Civix\CoreBundle\Tests\DataFixtures\ORM as ORM;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CiceroSyncCommandTest extends WebTestCase
{
    private $responseRepresentative;
    private $responseRepresentativeTitle;
    private $responseRepresentativeDistrict;
    private $responseRepresentativeNotFound;

    public function setUp()
    {
        $this->responseRepresentative = json_decode(
            file_get_contents(__DIR__.'/../Service/TestData/representative.json')
        );
        $this->responseRepresentativeTitle = json_decode(
            file_get_contents(__DIR__.'/../Service/TestData/representativeChangeTitle.json')
        );
        $this->responseRepresentativeDistrict = json_decode(
            file_get_contents(__DIR__.'/../Service/TestData/representativeChangeDistrict.json')
        );
        $this->responseRepresentativeNotFound = json_decode(
            file_get_contents(__DIR__.'/../Service/TestData/representativeNotFound.json')
        );
    }

    protected function tearDown()
    {
        $this->responseRepresentative = null;
        $this->responseRepresentativeTitle = null;
        $this->responseRepresentativeDistrict = null;
        $this->responseRepresentativeNotFound = null;
        parent::tearDown();
    }

    public function testSync()
    {
        $executor = $this->loadFixtures(array(
            ORM\LoadRepresentativeRelationData::class,
        ));
        /** @var Representative $representative */
        $representative = $executor->getReferenceRepository()->getReference('representative_jb');
        $cicero = $representative->getCiceroRepresentative();
        $districtId = $representative->getDistrict()->getId();

        $container = $this->getContainerForCheck($this->responseRepresentative);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking User One/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());
        /** @var Representative $representativeUpdated */
        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:Representative')->find($representative->getId());

        $this->assertEquals($cicero->getId(), $representativeUpdated->getCiceroRepresentative()->getId());
        $this->assertEquals($districtId, $representativeUpdated->getDistrict()->getId());
    }

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSyncLink()
    {
        $executor = $this->loadFixtures(array(
            ORM\LoadRepresentativeData::class,
        ));
        /** @var Representative $representative */
        $representative = $executor->getReferenceRepository()->getReference('representative_jb');
        $officialName = $representative->getOfficialTitle();
        $districtId = $representative->getDistrict()->getId();

        $container = $this->getContainerForCheck($this->responseRepresentative);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking User One/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());
        /** @var Representative $representativeUpdated */
        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:Representative')->find($representative->getId());
        $this->assertInstanceOf('Civix\CoreBundle\Entity\Representative', $representativeUpdated);

        //check links
        $this->assertNotNull(
            $representativeUpdated->getCiceroRepresentative(),
            'Cicero id in representative must be not null'
        );

        $this->assertEquals(
            $officialName, $representativeUpdated->getOfficialTitle(),
            'Official title should be changed'
        );
        $this->assertEquals(
            $districtId, $representativeUpdated->getDistrict()->getId(),
            'District of representative should not be changed'
        );
    }

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSyncWithChangedOfficialTitle()
    {
        $executor = $this->loadFixtures(array(
            ORM\LoadRepresentativeData::class,
        ));
        /** @var Representative $representative */
        $representative = $executor->getReferenceRepository()->getReference('representative_jb');
        $officialName = $representative->getOfficialTitle();
        $districtId = $representative->getDistrict()->getId();

        $container = $this->getContainerForCheck($this->responseRepresentativeTitle);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking User One/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());
        /** @var Representative $representativeUpdated */
        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:Representative')->find($representative->getId());

        $this->assertNotEquals(
            $officialName, $representativeUpdated->getOfficialTitle(),
            'Official title should be changed'
        );
        $this->assertEquals(
            $districtId, $representativeUpdated->getDistrict()->getId(),
            'District should n\'t be changed'
        );
    }

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSyncWithChangedOfficialTitleLink()
    {
        $executor = $this->loadFixtures(array(
            ORM\LoadRepresentativeData::class,
        ));
        /** @var Representative $representative */
        $representative = $executor->getReferenceRepository()->getReference('representative_jb');
        $districtId = $representative->getDistrict()->getId();

        $container = $this->getContainerForCheck($this->responseRepresentativeTitle);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking User One/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());
        /** @var Representative $representativeUpdated */
        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:Representative')->find($representative->getId());
        $this->assertInstanceOf('Civix\CoreBundle\Entity\Representative', $representativeUpdated);

        $this->assertEquals(
            $districtId, $representativeUpdated->getDistrict()->getId(),
            'District of storage representative should not be changed'
        );
    }

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSyncWithChangedDistrict()
    {
        $executor = $this->loadFixtures(array(
            ORM\LoadRepresentativeData::class,
        ));
        /** @var Representative $representative */
        $representative = $executor->getReferenceRepository()->getReference('representative_jb');
        $officialName = $representative->getOfficialTitle();
        $districtId = $representative->getDistrict()->getId();

        $container = $this->getContainerForCheck($this->responseRepresentativeDistrict);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking User One/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());
        /** @var Representative $representativeUpdated */
        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:Representative')->find($representative->getId());

        $this->assertEquals(
            $officialName, $representativeUpdated->getOfficialTitle(),
            'Official title should n\'t be changed'
        );
        $this->assertNotEquals(
            $districtId, $representativeUpdated->getDistrict()->getId(),
            'District should be changed'
        );
    }

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSyncWithChangedDistrictLink()
    {
        $executor = $this->loadFixtures(array(
            ORM\LoadRepresentativeData::class,
        ));

        $representative = $executor->getReferenceRepository()->getReference('representative_jb');
        $container = $this->getContainerForCheck($this->responseRepresentativeDistrict);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking User One/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());
        /** @var Representative $representativeUpdated */
        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:Representative')->find($representative->getId());
        $this->assertInstanceOf('Civix\CoreBundle\Entity\Representative', $representativeUpdated);
    }

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSyncRepresentativeNotFoundLink()
    {
        $executor = $this->loadFixtures(array(
            ORM\LoadRepresentativeData::class,
        ));

        $representative = $executor->getReferenceRepository()->getReference('representative_jb');
        $container = $this->getContainerForCheck($this->responseRepresentativeNotFound);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking User One/', $commandTester->getDisplay());
        $this->assertRegExp('/User One is not found and will be removed/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());
        /** @var Representative $representativeUpdated */
        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:Representative')->find($representative->getId());
        $this->assertInstanceOf('Civix\CoreBundle\Entity\Representative', $representativeUpdated);
        $this->assertNull(
            $representativeUpdated->getCiceroRepresentative(),
            'Representative should be removed from representative storage '.
            '(no link between representative and representative storage)'
        );
        $this->assertNull(
            $representativeUpdated->getDistrict(),
            'District should be null'
        );
    }

    private function getCommandTester($container)
    {
        $application = new Application();
        $application->add(new CiceroSyncCommand());
        /** @var CiceroSyncCommand $command */
        $command = $application->find('cicero:sync');
        $command->setContainer($container);
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        return $commandTester;
    }

    private function getContainerForCheck($ciceroReturnResult)
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $container = static::$kernel->getContainer();
        /** @var CiceroCalls|\PHPUnit_Framework_MockObject_MockObject $ciceroMock */
        $ciceroMock = $this->getMockBuilder('Civix\CoreBundle\Service\CiceroCalls')
            ->setMethods(array('getResponse'))
            ->disableOriginalConstructor()
            ->getMock();
        $ciceroMock->expects($this->any())
           ->method('getResponse')
           ->will($this->returnValue($ciceroReturnResult));
        /** @var OpenstatesApi|\PHPUnit_Framework_MockObject_MockObject $openStateServiceMock */
        $openStateServiceMock = $this->getMockBuilder('Civix\CoreBundle\Service\OpenstatesApi')
            ->setMethods(array('updateRepresentativeProfile'))
            ->disableOriginalConstructor()
            ->getMock();
        /** @var CongressApi|\PHPUnit_Framework_MockObject_MockObject $congressMock */
        $congressMock = $this->getMockBuilder('Civix\CoreBundle\Service\CongressApi')
            ->setMethods(array('updateRepresentativeProfile'))
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $converter = $this->createMock(ConverterInterface::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|CiceroApi $mock */
        $mock = new CiceroApi(
            $ciceroMock,
            $container->get('doctrine')->getManager(),
            $congressMock,
            $openStateServiceMock,
            $converter,
            $dispatcher
        );

        $fileSystem = $this->getMockBuilder('Knp\Bundle\GaufretteBundle\FilesystemMap')
            ->disableOriginalConstructor()
            ->getMock();
        $storage = $this->getMockBuilder('Vich\UploaderBundle\Storage\GaufretteStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $container->set('civix_core.cicero_calls', $ciceroMock);
        $container->set('civix_core.openstates_api', $openStateServiceMock);
        $container->set('civix_core.congress_api', $congressMock);
        $container->set('knp_gaufrette.filesystem_map', $fileSystem);
        $container->set('vich_uploader.storage.gaufrette', $storage);
        $container->set('civix_core.cicero_api', $mock);

        return $container;
    }
}
