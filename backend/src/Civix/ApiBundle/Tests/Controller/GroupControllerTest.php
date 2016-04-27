<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\ApiBundle\Tests\DataFixtures\ORM\LoadSuperuserData;
use Civix\ApiBundle\Tests\WebTestCase;
use FOS\RestBundle\Util\Codes;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class GroupControllerTest extends WebTestCase
{
	const API_GROUP_IS_OWNER_ENDPOINT   = '/api/groups/is-owner';
	const API_GROUP_IS_MEMBER_ENDPOINT  = '/api/groups/is-member';
	const API_GROUP_IS_MANAGER_ENDPOINT = '/api/groups/is-manager';
	
	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;
	
	private $client = null;

	public function setUp()
	{
		// Creates a initial client
		$this->client = static::createClient();

		/** @var AbstractExecutor $fixtures */
		$fixtures = $this->loadFixtures([
				LoadUserData::class,
				LoadGroupData::class,
				LoadUserGroupData::class,
				LoadSuperuserData::class
		]);
		
		$reference = $fixtures->getReferenceRepository();
		
		$this->em = $this->getContainer()->get('doctrine')->getManager();
		
		$this->group = $reference->getReference('group');
		
		$this->mobile1 = $reference->getReference('followertest');
		
		$this->mobile1_token = $this->getUserToken($this->mobile1->getUsername(), $this->mobile1->getUsername());
	}

	public function tearDown()
	{
		// Creates a initial client
		$this->client = NULL;
	}
	
	/**
	 * group api
	 */
	public function testGroupIsOwner()
	{
		$this->assertNotEmpty($this->mobile1_token, 'Login token should not empty');
		
		// Create a request scope context that allows serialize the question object
		$container = $this->getContainer();
		
		$request = Request::create('/');
		
		$request->setSession( new Session() );
		$container->enterScope('request');
		
		$container->set('request', $request, 'request');
		
		$content = [];
		
		// Test is owner endpoint for failed result
		$end_point = self::API_GROUP_IS_OWNER_ENDPOINT . '/' . $this->group->getId();
		
		$this->client->request('GET', $end_point, [], [], ['HTTP_Token' => $this->mobile1_token], $content);
		
		$response = $this->client->getResponse();
		
		$content = json_decode($response->getContent());

		$this->assertNotEmpty($content->error, 'The user is not owner of the group');
		
		$this->assertEquals(
				Codes::HTTP_BAD_REQUEST,
				$response->getStatusCode(),
				'Should be a 400 response'
				);
		
		$this->assertNotEmpty($response->headers->get('Access-Control-Allow-Origin'),
				'Should return cors headers');
		
		// Set as owner of the group the mobile user
		$this->group->setOwner($this->mobile1);

		$this->em->flush($this->group);
		
		// Test is owner endpoint for failed result
		$end_point = self::API_GROUP_IS_OWNER_ENDPOINT . '/' . $this->group->getId();

		$this->client->request('GET', $end_point, [], [], ['HTTP_Token' => $this->mobile1_token], $content);
		
		$response = $this->client->getResponse();
		
		$this->assertEquals(
				Codes::HTTP_NO_CONTENT,
				$response->getStatusCode(),
				'Should be a 204 response'
				);
		
		$this->assertNotEmpty($response->headers->get('Access-Control-Allow-Origin'),
				'Should return cors headers');
	}
}
