<?php

namespace Civix\CoreBundle\Tests\Command;

use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Service\CiceroApi;
use Civix\ApiBundle\Tests\WebTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Civix\CoreBundle\Command\CiceroSyncCommand;
use Civix\CoreBundle\Tests\DataFixtures\ORM as ORM;

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

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSynch()
    {
        $executor = $this->loadFixtures(array(
            ORM\LoadRepresentativeData::class,
        ));
        /** @var Representative $representative */
        $representative = $executor->getReferenceRepository()->getReference('representative_jb');
        $officialName = $representative->getOfficialTitle();
        $avatarSrc = $representative->getAvatarSrc();
        $districtId = $representative->getDistrictId();

        $container = $this->getContainerForCheck($this->responseRepresentative);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking Joseph Biden/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());
        /** @var Representative $representativeUpdated */
        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:Representative')->findOneByLastName('Biden');

        $this->assertTrue(
            $officialName == $representativeUpdated->getOfficialTitle(),
            'Official title should n\'t be changed'
        );
        $this->assertFalse(
            $avatarSrc == $representativeUpdated->getAvatarSrc(),
            'Avatar src should be changed'
        );
        $this->assertTrue(
            $districtId == $representativeUpdated->getDistrictId(),
            'District should n\'t be changed'
        );
    }

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSynchLink()
    {
        $executor = $this->loadFixtures(array(
            ORM\LoadRepresentativeData::class,
        ));

        $representativeSt = $executor->getReferenceRepository()->getReference('representative_jb');
        $officialName = $representativeSt->getOfficialTitle();
        $districtId = $representativeSt->getDistrictId();

        $container = $this->getContainerForCheck($this->responseRepresentative);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking Joseph Biden/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());
        /** @var Representative $representativeUpdated */
        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:Representative')->findOneByLastName('Biden');
        $this->assertInstanceOf('Civix\CoreBundle\Entity\Representative', $representativeUpdated);

        //check links
        $this->assertNotNull($representativeUpdated->getCiceroId(), 'Cicero id in representative must be not null');

        $this->assertTrue(
            $officialName == $representativeUpdated->getOfficialTitle(),
            'Official title of representative should not be changed'
        );
        $this->assertTrue(
            $districtId == $representativeUpdated->getDistrictId(),
            'District of representative should not be changed'
        );
    }

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSynchWithChangedOfficialTitle()
    {
        $executor = $this->loadFixtures(array(
            ORM\LoadRepresentativeData::class,
        ));

        $representative = $executor->getReferenceRepository()->getReference('representative_jb');
        $officialName = $representative->getOfficialTitle();
        $avatarSrc = $representative->getAvatarSrc();
        $districtId = $representative->getDistrictId();

        $container = $this->getContainerForCheck($this->responseRepresentativeTitle);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking Joseph Biden/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());
        /** @var Representative $representativeUpdated */
        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:Representative')->findOneByLastName('Biden');

        $this->assertFalse(
            $officialName == $representativeUpdated->getOfficialTitle(),
            'Official title should be changed'
        );
        $this->assertFalse(
            $avatarSrc == $representativeUpdated->getAvatarSrc(),
            'Avatar src should be changed'
        );
        $this->assertTrue(
            $districtId == $representativeUpdated->getDistrictId(),
            'District should n\'t be changed'
        );
    }

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSynchWithChangedOfficialTitleLink()
    {
        $executor = $this->loadFixtures(array(
            ORM\LoadRepresentativeData::class,
        ));

        $representativeSt = $executor->getReferenceRepository()->getReference('representative_jb');
        $districtId = $representativeSt->getDistrictId();

        $container = $this->getContainerForCheck($this->responseRepresentativeTitle);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking Joseph Biden/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());
        /** @var Representative $representativeUpdated */
        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:Representative')->findOneByLastName('Biden');
        $this->assertInstanceOf('Civix\CoreBundle\Entity\Representative', $representativeUpdated);

        $this->assertTrue(
            $districtId == $representativeUpdated->getDistrictId(),
            'District of storage representative should not be changed'
        );
    }

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSynchWithChangedDistrict()
    {
        $executor = $this->loadFixtures(array(
            ORM\LoadRepresentativeData::class,
        ));

        $representative = $executor->getReferenceRepository()->getReference('representative_jb');
        $officialName = $representative->getOfficialTitle();
        $avatarSrc = $representative->getAvatarSrc();
        $districtId = $representative->getDistrictId();

        $container = $this->getContainerForCheck($this->responseRepresentativeDistrict);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking Joseph Biden/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());
        /** @var Representative $representativeUpdated */
        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:Representative')->findOneByLastName('Biden');

        $this->assertTrue(
            $officialName == $representativeUpdated->getOfficialTitle(),
            'Official title should n\'t be changed'
        );
        $this->assertFalse(
            $avatarSrc == $representativeUpdated->getAvatarSrc(),
            'Avatar src should be changed'
        );
        $this->assertFalse(
            $districtId == $representativeUpdated->getDistrictId(),
            'District should be changed'
        );
    }

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSynchWithChangedDistrictLink()
    {
        $this->loadFixtures(array(
            ORM\LoadRepresentativeData::class,
        ));

        $container = $this->getContainerForCheck($this->responseRepresentativeDistrict);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking Joseph Biden/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());
        /** @var Representative $representativeUpdated */
        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:Representative')->findOneByLastName('Biden');
        $this->assertInstanceOf('Civix\CoreBundle\Entity\Representative', $representativeUpdated);
    }

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSynchRepresentativeNotFoundLink()
    {
        $this->loadFixtures(array(
            ORM\LoadRepresentativeData::class,
        ));

        $container = $this->getContainerForCheck($this->responseRepresentativeNotFound);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking Joseph Biden/', $commandTester->getDisplay());
        $this->assertRegExp('/Joseph Biden is not found and will be removed/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());
        /** @var Representative $representativeUpdated */
        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:Representative')->findOneByLastName('Biden');
        $this->assertInstanceOf('Civix\CoreBundle\Entity\Representative', $representativeUpdated);
        $this->assertNull(
            $representativeUpdated->getCiceroId(),
            'Representative should be removed from representative storage '.
            '(no link between representative and representative storage)'
        );
        $this->assertNull(
            $representativeUpdated->getDistrictId(),
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

        $ciceroMock = $this->getMock('Civix\CoreBundle\Service\CiceroCalls',
            array('getResponse'),
            array(),
            '',
            false
        );
        $ciceroMock->expects($this->any())
           ->method('getResponse')
           ->will($this->returnValue($ciceroReturnResult));

        $openstateServiceMock = $this->getMock('Civix\CoreBundle\Service\OpenstatesApi',
            array('updateReprStorageProfile'),
            array(),
            '',
            false
        );

        $congressMock = $this->getMock('Civix\CoreBundle\Service\CongressApi',
            array('updateReprStorageProfile'),
            array(),
            '',
            false
        );

        /** @var \PHPUnit_Framework_MockObject_MockObject|CiceroApi $mock */
        $mock = $this->getMock('Civix\CoreBundle\Service\CiceroApi',
            array('checkLink'),
            array(),
            '',
            false
        );
        $mock->setEntityManager($container->get('doctrine')->getManager());
        $mock->setCongressApi($congressMock);
        $mock->setOpenstatesApi($openstateServiceMock);

        $fileSystem = $this->getMockBuilder('Knp\Bundle\GaufretteBundle\FilesystemMap')
            ->disableOriginalConstructor()
            ->getMock();
        $storage = $this->getMockBuilder('Vich\UploaderBundle\Storage\GaufretteStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $container->set('civix_core.cicero_calls', $ciceroMock);
        $container->set('civix_core.openstates_api', $openstateServiceMock);
        $container->set('civix_core.congress_api', $congressMock);
        $container->set('knp_gaufrette.filesystem_map', $fileSystem);
        $container->set('vich_uploader.storage.gaufrette', $storage);
        $container->set('civix_core.cicero_api', $mock);

        return $container;
    }
}
