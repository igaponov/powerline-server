<?php
namespace Tests\Civix\ApiBundle\Controller\V2\Representative;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserRepresentativeData;
use Symfony\Bundle\FrameworkBundle\Client;

class AnnouncementControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/representatives/{representative}/announcements';
	
	/**
	 * @var Client
	 */
	private $client;

	public function setUp()
	{
		$this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
	}

	public function tearDown()
	{
		$this->client = NULL;
        parent::tearDown();
	}

	public function testCreateAnnouncementWithWrongCredentials()
	{
        $repository = $this->loadFixtures([
            LoadUserRepresentativeData::class,
        ])->getReferenceRepository();
		$client = $this->client;
        $representative = $repository->getReference('representative_jb');
        $uri = str_replace('{representative}', $representative->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Token' => 'user4']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	/**
	 * @param array $params
	 * @param array $errors
	 * @dataProvider getInvalidParams
	 */
	public function testCreateAnnouncementReturnsErrors($params, $errors)
	{
        $repository = $this->loadFixtures([
            LoadUserRepresentativeData::class,
        ])->getReferenceRepository();
		$client = $this->client;
        $representative = $repository->getReference('representative_jb');
        $uri = str_replace('{representative}', $representative->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Token' => 'user1'], json_encode($params));
		$this->assertResponseHasErrors($client->getResponse(), $errors);
	}

    public function getInvalidParams()
    {
        return [
            'empty content' => [
                [
                    'content' => '',
                ],
                [
                    'The announcement should not be blank.',
                    'content' => 'This value should not be blank.',
                ],
            ],
            'invalid' => [
                [
                    'content' => 'test',
                    'image' => base64_encode(file_get_contents(__FILE__)),
                ],
                [
                    'image' => 'This file is not a valid image.',
                ],
            ],
        ];
    }

	public function testCreateAnnouncementIsOk()
	{
        $repository = $this->loadFixtures([
            LoadUserRepresentativeData::class,
        ])->getReferenceRepository();
        $representative = $repository->getReference('representative_jb');
        $params = [
			'content' => 'some text',
		];
        $client = $this->client;
        $uri = str_replace('{representative}', $representative->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Token' => 'user1'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertNotNull($data['id']);
		$this->assertSame($params['content'], $data['content_parsed']);
	}
}