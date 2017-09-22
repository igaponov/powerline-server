<?php

namespace Tests\Civix\CoreBundle;

use GuzzleHttp\Client;
use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Parser;

class ProPublicaClientTest extends TestCase
{
    /**
     * @var Parser
     */
    private $parser;
    /**
     * @var array
     */
    private $container = [];
    /**
     * @var MockHandler
     */
    private $mockHandler;
    /**
     * @var GuzzleClient
     */
    private $guzzleClient;

    protected function setUp()
    {
        parent::setUp();
        $this->parser = new Parser();
        $this->mockHandler = new MockHandler();
        $handler = HandlerStack::create($this->mockHandler);
        $handler->push(Middleware::history($this->container));
        $client = new Client(['handler' => $handler]);
        $data = $this->parser->parse(file_get_contents(
            __DIR__.'/../../../src/Civix/CoreBundle/Resources/config/propublica.yml'
        ));
        $description = new Description($data['parameters']['civix_core.propublica_description']);
        $this->guzzleClient = new GuzzleClient($client, $description);
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->container = [];
        $this->parser = $this->mockHandler = $this->guzzleClient = null;
    }

    public function testGetMember(): void
    {
        $content = file_get_contents(__DIR__.'/data/propublica_member.json');
        $this->mockHandler->append(new Response(200, [], $content));
        $command = $this->guzzleClient->getCommand('getMember', ['id' => 'H000107']);
        $result = $this->guzzleClient->execute($command);
        $this->assertSame(json_decode($content, true), $result->toArray());
        $this->assertCount(1, $this->container);
        /** @var Request $request */
        $request = $this->container[0]['request'];
        $this->assertArrayHasKey('X-API-Key', $request->getHeaders());
        $this->assertNotEmpty($request->getHeader('X-API-Key'));
        $this->assertSame(
            'https://api.propublica.org/congress/v1/members/H000107.json',
            (string)$request->getUri()
        );
    }

    public function testGetNewMembers(): void
    {
        $content = file_get_contents(__DIR__.'/data/propublica_new_members.json');
        $this->mockHandler->append(new Response(200, [], $content));
        $command = $this->guzzleClient->getCommand('getNewMembers', ['keyz' => 'QEW']);
        $result = $this->guzzleClient->execute($command);
        $this->assertSame(json_decode($content, true), $result->toArray());
        $this->assertCount(1, $this->container);
        /** @var Request $request */
        $request = $this->container[0]['request'];
        $this->assertArrayHasKey('X-API-Key', $request->getHeaders());
        $this->assertNotEmpty($request->getHeader('X-API-Key'));
        $this->assertSame(
            'https://api.propublica.org/congress/v1/members/new.json',
            (string)$request->getUri()
        );
    }

    public function testGetVotePositions(): void
    {
        $content = file_get_contents(__DIR__.'/data/propublica_vote_positions.json');
        $this->mockHandler->append(new Response(200, [], $content));
        $command = $this->guzzleClient->getCommand('getVotePositions', ['id' => 'H000107']);
        $result = $this->guzzleClient->execute($command);
        $this->assertSame(json_decode($content, true), $result->toArray());
        $this->assertCount(1, $this->container);
        /** @var Request $request */
        $request = $this->container[0]['request'];
        $this->assertArrayHasKey('X-API-Key', $request->getHeaders());
        $this->assertNotEmpty($request->getHeader('X-API-Key'));
        $this->assertSame(
            'https://api.propublica.org/congress/v1/members/H000107/votes.json',
            (string)$request->getUri()
        );
    }
}