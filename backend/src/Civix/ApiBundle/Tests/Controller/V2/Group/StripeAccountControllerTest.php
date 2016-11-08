<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Group;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Stripe\AccountGroup;
use Civix\CoreBundle\Service\Stripe;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Stripe\LoadAccountGroupData;
use Symfony\Bundle\FrameworkBundle\Client;

class StripeAccountControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/stripe-accounts';

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

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForRequest
     */
    public function testDeleteAccountWithWrongCredentialsThrowsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadAccountGroupData::class,
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $account = $repository->getReference($reference);
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$account->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
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
            ->with($this->isInstanceOf(AccountGroup::class));
        $repository = $this->loadFixtures([
            LoadAccountGroupData::class,
        ])->getReferenceRepository();
        $account = $repository->getReference('stripe_account_1');
        $client = $this->client;
        $client->getContainer()->set('civix_core.stripe', $service);
        $client->request('DELETE', self::API_ENDPOINT.'/'.$account->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
    }

    public function getInvalidGroupCredentialsForRequest()
    {
        return [
            'manager' => ['user2', 'stripe_account_1'],
            'member' => ['user4', 'stripe_account_1'],
            'outlier' => ['followertest', 'stripe_account_1'],
        ];
    }
}