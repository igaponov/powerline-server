<?php

namespace Tests\Civix\ApiBundle\Controller\PublicApi;

use Civix\ApiBundle\Controller\PublicApi\PhoneController;
use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Service\Authy;
use libphonenumber\PhoneNumber;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class PhoneControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    public function tearDown(): void
    {
        $this->client = NULL;
        parent::tearDown();
    }

    public function testStartVerification()
    {
        $service = $this->getMockBuilder(Authy::class)
            ->disableOriginalConstructor()
            ->setMethods(['startVerification'])
            ->getMock();
        $service->expects($this->once())
            ->method('startVerification')
            ->with($this->callback(function (PhoneNumber $phoneNumber) {
                $this->assertSame(1, $phoneNumber->getCountryCode());
                $this->assertSame('8005551111', $phoneNumber->getNationalNumber());

                return true;
            }));
        $this->client->getContainer()->set('civix_core.service.authy', $service);
        $params = ['phone' => '+18005551111'];
        $this->client->request('POST', '/api-public/phone/verification', [], [], [], json_encode($params));
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame('ok', $response->getContent());
    }

    public function testStartVerificationWithWrongNumber()
    {
        $params = ['phone' => '+1111111111'];
        $this->client->request('POST', '/api-public/phone/verification', [], [], [], json_encode($params));
        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
    }
}
