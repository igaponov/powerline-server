<?php
namespace Civix\ApiBundle\Tests\EventListener;

use Civix\ApiBundle\EventListener\PetitionMetadataListener;
use Civix\CoreBundle\Entity\Micropetitions\Metadata;
use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Civix\CoreBundle\Service\HTMLMetadataParser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use GuzzleHttp\Psr7\Response;

class PetitionMetadataListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testPostPersistParsePetitionBodyAndSetsMetadataUrl()
    {
        $petition = new Petition();
        $petition->setPetitionBody('Some text with http://url.tld for parsing.');
        $parser = $this->getMockBuilder(HTMLMetadataParser::class)
            ->setMethods(['parse'])
            ->getMock();
        $metadata = new Metadata();
        $metadata->setTitle('title');
        $parser->expects($this->once())
            ->method('parse')
            ->with('body')
            ->will($this->returnValue($metadata));
        /** @var \PHPUnit_Framework_MockObject_MockObject|PetitionMetadataListener $listener */
        $listener = $this->getMockBuilder(PetitionMetadataListener::class)
            ->setConstructorArgs([$parser])
            ->setMethods(['getResponse'])
            ->getMock();
        $response = new Response(200, [], 'body');
        $listener->expects($this->once())
            ->method('getResponse')
            ->with('http://url.tld')
            ->will($this->returnValue($response));
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event = new LifecycleEventArgs($petition, $em);
        $listener->postPersist($event);
        $this->assertEquals('http://url.tld', $metadata->getUrl());
    }
}