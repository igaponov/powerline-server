<?php

namespace Tests\Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Subscription\Subscription;
use Civix\CoreBundle\Service\Subscription\SubscriptionManager;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadAdvancedAttributesData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupOwnerData;
use Symfony\Bundle\FrameworkBundle\Client;

class AdvancedAttributesControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/groups/{group}/advanced-attributes';

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

    public function testGetAttributesWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadAdvancedAttributesData::class
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer user1']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param $fixtures
     * @param $user
     * @param $reference
     * @dataProvider getValidGroupCredentialsForGetRequest
     */
    public function testGetAttributesIsOk($fixtures, $user, $reference)
    {
        $repository = $this->loadFixtures(
            array_merge([LoadAdvancedAttributesData::class], $fixtures)
        )->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference($reference);
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer '.$user]);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $attrs = $group->getAdvancedAttributes();
        $this->assertSame($attrs->getWelcomeMessage(), $data['welcome_message']);
        $this->assertSame($attrs->getWelcomeVideo(), $data['welcome_video']);
    }

    /**
     * @param $fixtures
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForPutRequest
     */
    public function testUpdateSubscriptionWithWrongCredentialsThrowsException($fixtures, $user, $reference)
    {
        $repository = $this->loadFixtures(
            array_merge([LoadAdvancedAttributesData::class], $fixtures)
        )->getReferenceRepository();
        $group = $repository->getReference($reference);
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer '.$user]);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testUpdateSubscriptionWithFreeSubscription()
    {
        $repository = $this->loadFixtures([
            LoadAdvancedAttributesData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $service = $this->getMockBuilder(SubscriptionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $subscription = new Subscription();
        $subscription->setPackageType(Subscription::PACKAGE_TYPE_FREE);
        $service->expects($this->once())
            ->method('getSubscription')
            ->with($this->isInstanceOf(Group::class))
            ->willReturn($subscription);
        $client = $this->client;
        $client->getContainer()->set('civix_core.subscription_manager', $service);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $params = ['welcome_message' => 'hello!', 'welcome_video' => 'url'];
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer user1'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testUpdateSubscriptionReturnsErrors()
    {
        $repository = $this->loadFixtures([
            LoadAdvancedAttributesData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $service = $this->getMockBuilder(SubscriptionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $subscription = new Subscription();
        $subscription->setPackageType(Subscription::PACKAGE_TYPE_GOLD);
        $service->expects($this->once())
            ->method('getSubscription')
            ->with($this->isInstanceOf(Group::class))
            ->willReturn($subscription);
        $client = $this->client;
        $client->getContainer()->set('civix_core.subscription_manager', $service);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $params = ['welcome_video' => 'invalid url'];
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer user1'], json_encode($params));
        $response = $client->getResponse();
        $this->assertResponseHasErrors($response, [
            'welcome_video' => 'This value is not a valid URL.',
        ]);
    }

    /**
     * @param $fixtures
     * @param $user
     * @param $reference
     * @dataProvider getValidGroupCredentialsForPutRequest
     */
    public function testUpdateSubscriptionIsOk($fixtures, $user, $reference)
    {
        $repository = $this->loadFixtures(
            array_merge([LoadAdvancedAttributesData::class], $fixtures)
        )->getReferenceRepository();
        $group = $repository->getReference($reference);
        $service = $this->getMockBuilder(SubscriptionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $subscription = new Subscription();
        $subscription->setPackageType(Subscription::PACKAGE_TYPE_GOLD);
        $service->expects($this->once())
            ->method('getSubscription')
            ->with($this->isInstanceOf(Group::class))
            ->willReturn($subscription);
        $client = $this->client;
        $client->getContainer()->set('civix_core.subscription_manager', $service);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $params = ['welcome_message' => 'hello!', 'welcome_video' => 'http://example.com/video'];
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer '.$user], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($params['welcome_message'], $data['welcome_message']);
        $this->assertSame($params['welcome_video'], $data['welcome_video']);
    }

    public function getValidGroupCredentialsForGetRequest()
    {
        return [
            'owner' => [[LoadUserGroupOwnerData::class], 'user3', 'group_3'],
            'manager' => [[LoadGroupManagerData::class], 'user2', 'group_3'],
            'member' => [[LoadUserGroupData::class], 'user4', 'group_3'],
        ];
    }

    public function getInvalidGroupCredentialsForPutRequest()
    {
        return [
            'member' => [[LoadUserGroupData::class], 'user4', 'group_3'],
            'outlier' => [[], 'user1', 'group_3'],
        ];
    }

    public function getValidGroupCredentialsForPutRequest()
    {
        return [
            'owner' => [[LoadUserGroupOwnerData::class], 'user3', 'group_3'],
            'manager' => [[LoadGroupManagerData::class], 'user2', 'group_3'],
        ];
    }
}
