<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFieldsData;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFollowerTestData;
use Symfony\Bundle\FrameworkBundle\Client;

class FieldControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/group/fields';

	/**
	 * @var null|Client
	 */
	private $client = null;

	/**
	 * @var AbstractExecutor
	 */
	private $executor;

	public function setUp()
	{
		$this->executor = $this->loadFixtures([
			LoadGroupFollowerTestData::class,
			LoadGroupFieldsData::class,
		]);
		// Creates a initial client
		$this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
	}

	public function tearDown()
	{
		// Creates a initial client
		$this->client = NULL;
	}

	public function testGetGroupFieldsIsOk()
	{
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Token'=>'secret_token']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertCount(5, $data);
		$this->assertSame('test-group-field', $data[0]['field_name']);
	}

	/**
	 * @param array $params
	 * @param array $errors
	 * @dataProvider getInvalidParams
	 */
	public function testCreateGroupFieldReturnsErrors($params, $errors)
	{
		$client = $this->client;
		$client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Token'=>'secret_token'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
		$children = $data['errors']['children'];
		foreach ($errors as $child => $error) {
			$this->assertEquals([$error], $children[$child]['errors']);
		}
	}

	/**
	 * @param $params
	 * @dataProvider getValidParams
	 */
	public function testCreateGroupFieldIsOk($params)
	{
		$client = $this->client;
		$client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Token'=>'secret_token'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($params['field_name'], $data['field_name']);
	}

	/**
	 * @param array $params
	 * @param array $errors
	 * @dataProvider getInvalidParams
	 */
	public function testUpdateGroupFieldReturnsErrors($params, $errors)
	{
		$client = $this->client;
		$field = $this->executor->getReferenceRepository()->getReference('test-group-field');
		$client->request('PUT', self::API_ENDPOINT.'/'.$field->getId(), [], [], ['HTTP_Token'=>'secret_token'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
		$children = $data['errors']['children'];
		foreach ($errors as $child => $error) {
			$this->assertEquals([$error], $children[$child]['errors']);
		}
	}

	public function testUpdateGroupFieldWithWrongCredentialsReturnsException()
	{
		$client = $this->client;
		$field = $this->executor->getReferenceRepository()
			->getReference('anothers-group-field');
		$client->request('PUT', self::API_ENDPOINT.'/'.$field->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="group" token="secret_token"'], '{}');
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	/**
	 * @param $params
	 * @dataProvider getValidParams
	 */
	public function testUpdateGroupFieldIsOk($params)
	{
		$field = $this->executor->getReferenceRepository()->getReference('test-group-field');
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$field->getId(), [], [], ['HTTP_Token'=>'secret_token'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($field->getId(), $data['id']);
		$this->assertSame($params['field_name'], $data['field_name']);
	}

	public function getInvalidParams()
	{
		return [
			'empty name' => [
				[],
				[
					'field_name' => 'This value should not be blank.',
				]
			],
		];
	}

	public function getValidParams()
	{
		return [
			'with name' => [
				[
					'field_name' => 'some-name',
				]
			],
		];
	}

	public function testDeleteGroupFieldWithWrongCredentialsReturnsException()
	{
		$client = $this->client;
		$field = $this->executor->getReferenceRepository()
			->getReference('anothers-group-field');
		$client->request('DELETE', self::API_ENDPOINT.'/'.$field->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="group" token="secret_token"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testDeleteGroupFieldIsOk()
	{
		$field = $this->executor->getReferenceRepository()->getReference('test-group-field');
		$client = $this->client;
		$client->request('DELETE', self::API_ENDPOINT.'/'.$field->getId(), [], [], ['HTTP_Token'=>'secret_token']);
		$response = $client->getResponse();
		$this->assertEquals(204, $response->getStatusCode(), $response->getContent());
	}
}