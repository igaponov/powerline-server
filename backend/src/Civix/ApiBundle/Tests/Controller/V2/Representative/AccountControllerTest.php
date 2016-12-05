<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Representative;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Stripe\AccountRepresentative;
use Civix\CoreBundle\Service\Stripe;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Stripe\LoadAccountRepresentativeData;
use Symfony\Bundle\FrameworkBundle\Client;

class AccountControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/representatives/{representative}/stripe-account';

    /**
     * @var null|Client
     */
    private $client = null;

    public function setUp()
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    public function tearDown()
    {
        $this->client = NULL;
        parent::tearDown();
    }

    public function testDeleteAccountWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadAccountRepresentativeData::class,
        ])->getReferenceRepository();
        $representative = $repository->getReference('representative_jb');
        $client = $this->client;
        $uri = str_replace('{representative}', $representative->getId(), self::API_ENDPOINT);
        $client->request('DELETE', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user2"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testDeleteAccountIsOk()
    {
        $service = $this->getMockBuilder(Stripe::class)
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->once())
            ->method('deleteAccount')
            ->with($this->isInstanceOf(AccountRepresentative::class));
        $repository = $this->loadFixtures([
            LoadAccountRepresentativeData::class,
        ])->getReferenceRepository();
        $representative = $repository->getReference('representative_jb');
        $client = $this->client;
        $client->getContainer()->set('civix_core.stripe', $service);
        $uri = str_replace('{representative}', $representative->getId(), self::API_ENDPOINT);
        $client->request('DELETE', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
    }
}