<?php
namespace Civix\ApiBundle\Tests\EventListener;

use Civix\ApiBundle\EventListener\MetadataListener;
use Civix\CoreBundle\Entity\Metadata;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Service\HTMLMetadataParser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class MetadataListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testPostPersistParsePostBodyWithWrongUrls()
    {
        $entity = new Post();
        $entity->setBody('Some text with http://url.tld , wrong.urls, https://example.com and another.normal.com:8000/qwerty/890 for parsing.');
        $history = [];
        $client = $this->getClientMock([
            new RequestException('Error Communicating with Server', new Request('GET', 'url.tld')),
            new ConnectException('cURL error 6: Could not resolve host: wrong.urls (see http://curl.haxx.se/libcurl/c/libcurl-errors.html)', new Request('GET', 'wrong.urls')),
            new Response(200, [], 'third request body'),
            new Response(500, [], ''),
        ], $history);
        $parser = $this->getParserMock();
        $metadata = new Metadata();
        $metadata->setTitle('title');
        $parser->expects($this->once())
            ->method('parse')
            ->with('third request body')
            ->willReturn($metadata);
        /** @var \PHPUnit_Framework_MockObject_MockObject|MetadataListener $listener */
        $listener = new MetadataListener($client, $parser);
        $em = $this->getManagerMock();
        $event = new LifecycleEventArgs($entity, $em);
        $listener->postPersist($event);
        $this->assertEquals('https://example.com', $metadata->getUrl());
        $this->assertCount(3, $history);
        foreach (['http://url.tld', 'wrong.urls', 'https://example.com'] as $key => $uri) {
            /** @var Request $request */
            $request = $history[$key]['request'];
            $this->assertEquals($uri, (string)$request->getUri());
        }
    }

    /**
     * @param Post|UserPetition $entity
     * @dataProvider getEntities
     */
    public function testPostPersistParsePostBodyAndSetsMetadataUrl($entity)
    {
        $entity->setBody('Some text with http://url.tld for parsing.');
        $response = new Response(200, [], 'body');
        $history = [];
        $client = $this->getClientMock([$response], $history);
        $parser = $this->getParserMock();
        $metadata = new Metadata();
        $metadata->setTitle('title');
        $parser->expects($this->once())
            ->method('parse')
            ->with('body')
            ->will($this->returnValue($metadata));
        $listener = new MetadataListener($client, $parser);
        $em = $this->getManagerMock();
        $event = new LifecycleEventArgs($entity, $em);
        $listener->postPersist($event);
        $this->assertEquals('http://url.tld', $metadata->getUrl());
        /** @var Request $request */
        $request = $history[0]['request'];
        $this->assertEquals('http://url.tld', (string)$request->getUri());
    }

    /**
     * @param Post|UserPetition $entity
     * @dataProvider getEntities
     */
    public function testPostPersistParsePostBodyAndSetsMetadataImage($entity)
    {
        $entity->setBody('Some text with http://url.tld/image.jpg for parsing.');
        $history = [];
        $client = $this->getClientMock([new Response(200, ['content-type' => 'image/jpeg'], 'body')], $history);
        $parser = $this->getParserMock();
        $parser->expects($this->never())
            ->method('parse');
        /** @var \PHPUnit_Framework_MockObject_MockObject|MetadataListener $listener */
        $listener = new MetadataListener($client, $parser);
        $em = $this->getManagerMock();
        $event = new LifecycleEventArgs($entity, $em);
        $listener->postPersist($event);
        $metadata = $entity->getMetadata();
        $this->assertEquals('http://url.tld/image.jpg', $metadata->getUrl());
        $this->assertEquals('http://url.tld/image.jpg', $metadata->getImage());
        $this->assertNull($metadata->getTitle());
        /** @var Request $request */
        $request = $history[0]['request'];
        $this->assertEquals('http://url.tld/image.jpg', (string)$request->getUri());
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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|HTMLMetadataParser
     */
    private function getParserMock()
    {
        return $this->getMockBuilder(HTMLMetadataParser::class)
            ->setMethods(['parse'])
            ->getMock();
    }

    /**
     * @param array $responses
     * @param array $container
     * @return Client
     */
    private function getClientMock(array $responses, array &$container = [])
    {
        $history = Middleware::history($container);
        $mock = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $handler->push($history);

        return new Client(['handler' => $handler]);
    }
}