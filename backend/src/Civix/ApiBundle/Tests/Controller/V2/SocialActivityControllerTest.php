<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFollowerTestData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSocialActivityData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Symfony\Bundle\FrameworkBundle\Client;

class SocialActivityControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/social-activities/';

	/**
	 * @var Client
	 */
	private $client = null;

	/**
	 * @var ReferenceRepository
	 */
	private $repository;

	public function setUp()
	{
		// Creates a initial client
		$this->client = static::createClient();

		$this->repository = $this->loadFixtures([
			LoadUserData::class,
			LoadGroupFollowerTestData::class,
			LoadSocialActivityData::class,
		])->getReferenceRepository();
	}

	public function tearDown()
	{
		// Creates a initial client
		$this->client = NULL;
	}

	public function testUpdateSocialActivityWithWrongCredentialsReturnsException()
	{
		/** @var SocialActivity $activity */
		$activity = $this->repository->getReference('social_activity_11');
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.$activity->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"'], '');
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testUpdateSocialActivityIsOk()
	{
		/** @var SocialActivity $activity */
		$activity = $this->repository->getReference('social_activity_1');
		$params = ['ignore' => !$activity->isIgnore()];
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.$activity->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($params['ignore'], $data['ignore']);
	}

	public function testDeleteSocialActivityWithWrongCredentialsReturnsException()
	{
		/** @var SocialActivity $activity */
		$activity = $this->repository->getReference('social_activity_11');
		$client = $this->client;
		$client->request('DELETE', self::API_ENDPOINT.$activity->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testDeleteSocialActivityIsOk()
	{
		/** @var SocialActivity $activity */
		$activity = $this->repository->getReference('social_activity_1');
		$client = $this->client;
		$client->request('DELETE', self::API_ENDPOINT.$activity->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
		$response = $client->getResponse();
		$this->assertEquals(204, $response->getStatusCode(), $response->getContent());
	}
}
