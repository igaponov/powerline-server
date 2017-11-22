<?php

namespace Tests\Civix\FrontBundle\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\RecoveryToken;
use Symfony\Bundle\FrameworkBundle\Client;
use Tests\Civix\CoreBundle\DataFixtures\ORM\LoadRecoveryTokenData;

class SecurityControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    public function setUp(): void
    {
        // Creates a initial client
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    public function tearDown(): void
    {
        $this->client = NULL;
        parent::tearDown();
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::bootFixtureLoader();
        self::$fixtureLoader->loadFixtures([
            LoadRecoveryTokenData::class,
        ]);
    }

    public function testRecoverToken()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var RecoveryToken $token */
        $token = $repository->getReference('recovery_token_1');
        $crawler = $this->client->request('GET', '/security/recovery/'.$token->getToken());
        $this->assertContains(
            'E-mail confirmed. Please return to the Powerline app.',
            $crawler->text()
        );
        $token = $this->getContainer()->get('doctrine.orm.entity_manager')->find(RecoveryToken::class, $token->getId());
        $this->assertTrue($token->isConfirmed());
    }

    public function testRecoverInvalidToken()
    {
        $this->client->request('GET', '/security/recovery/xxx');
        $response = $this->client->getResponse();
        $this->assertSame(404, $response->getStatusCode(), $response->getContent());
    }
}
