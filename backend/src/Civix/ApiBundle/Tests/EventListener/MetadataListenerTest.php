<?php
namespace Civix\ApiBundle\Tests\EventListener;

use Civix\ApiBundle\EventListener\MetadataListener;
use Civix\CoreBundle\Entity\Metadata;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Service\HTMLMetadataParser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use GuzzleHttp\Psr7\Response;

class MetadataListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param Post|UserPetition $entity
     * @dataProvider getEntities
     */
    public function testPostPersistParsePostBodyAndSetsMetadataUrl($entity)
    {
        $entity->setBody('Some text with http://url.tld for parsing.');
        $parser = $this->getMockBuilder(HTMLMetadataParser::class)
            ->setMethods(['parse'])
            ->getMock();
        $metadata = new Metadata();
        $metadata->setTitle('title');
        $parser->expects($this->once())
            ->method('parse')
            ->with('body')
            ->will($this->returnValue($metadata));
        /** @var \PHPUnit_Framework_MockObject_MockObject|MetadataListener $listener */
        $listener = $this->getMockBuilder(MetadataListener::class)
            ->setConstructorArgs([$parser])
            ->setMethods(['getResponse'])
            ->getMock();
        $response = new Response(200, [], 'body');
        $listener->expects($this->once())
            ->method('getResponse')
            ->with('http://url.tld')
            ->will($this->returnValue($response));
        $em = $this->getManagerMock();
        $event = new LifecycleEventArgs($entity, $em);
        $listener->postPersist($event);
        $this->assertEquals('http://url.tld', $metadata->getUrl());
    }

    /**
     * @param Post|UserPetition $entity
     * @dataProvider getEntities
     */
    public function testPostPersistParsePostBodyAndSetsMetadataImage($entity)
    {
        $entity->setBody('Some text with http://url.tld/image.jpg for parsing.');
        $parser = $this->getMockBuilder(HTMLMetadataParser::class)
            ->setMethods(['parse'])
            ->getMock();
        $parser->expects($this->never())
            ->method('parse');
        /** @var \PHPUnit_Framework_MockObject_MockObject|MetadataListener $listener */
        $listener = $this->getMockBuilder(MetadataListener::class)
            ->setConstructorArgs([$parser])
            ->setMethods(['getResponse'])
            ->getMock();
        $response = new Response(200, ['content-type' => 'image/jpeg'], 'body');
        $listener->expects($this->once())
            ->method('getResponse')
            ->with('http://url.tld/image.jpg')
            ->willReturn($response);
        $em = $this->getManagerMock();
        $event = new LifecycleEventArgs($entity, $em);
        $listener->postPersist($event);
        $metadata = $entity->getMetadata();
        $this->assertEquals('http://url.tld/image.jpg', $metadata->getUrl());
        $this->assertEquals('http://url.tld/image.jpg', $metadata->getImage());
        $this->assertNull($metadata->getTitle());
    }

    public function getEntities()
    {
        return [
            'post' => [new Post],
            'petition' => [new UserPetition],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    private function getManagerMock()
    {
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $em;
    }
}