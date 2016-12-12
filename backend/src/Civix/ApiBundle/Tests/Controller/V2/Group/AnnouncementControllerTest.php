<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Group;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFollowerTestData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupRepresentativesData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupSectionData;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Client;

class AnnouncementControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/groups/{group}/announcements';
	
	/**
	 * @var Client
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

	public function testCreateAnnouncementWithWrongCredentials()
	{
        $repository = $this->loadFixtures([
            LoadGroupFollowerTestData::class,
        ])->getReferenceRepository();
		$client = $this->client;
        $group = $repository->getReference('group');
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Token'=>'userfollowtest1']);
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
            LoadGroupFollowerTestData::class,
        ])->getReferenceRepository();
		$client = $this->client;
        $group = $repository->getReference('testfollowsecretgroups');
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Token'=>'userfollowtest1'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
		$children = $data['errors']['children'];
		foreach ($errors as $child => $error) {
			$this->assertEquals([$error], $children[$child]['errors']);
		}
	}

    public function getInvalidParams()
    {
        return [
            'empty content' => [
                [
                    'content' => '',
                ],
                [
                    'content' => 'This value should not be blank.',
                ]
            ],
        ];
    }

    /**
     * @param $fixtures
     * @param $user
     * @param $reference
     * @dataProvider getValidAnnouncementCredentialsForUpdateRequest
     */
	public function testCreateAnnouncementIsOk($fixtures, $user, $reference)
	{
        $repository = $this->loadFixtures(
            array_merge([LoadGroupSectionData::class], $fixtures)
        )->getReferenceRepository();
        $group = $repository->getReference($reference);
        $section1 = $repository->getReference($reference.'_section_1');
        $section2 = $repository->getReference($reference.'_section_2');
        $params = [
			'content' => 'some text',
            'group_sections' => [$section1->getId(), $section2->getId()],
		];
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Token'=>$user], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertNotNull($data['id']);
		$this->assertSame($params['content'], $data['content_parsed']);
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM announcement_sections ps WHERE group_section_id IN (?, ?) AND announcement_id = ?', [$section1->getId(), $section2->getId(), $data['id']]);
        $this->assertEquals(2, $count);
	}

	public function testCreateAnnouncementWithInvalidGroupSection()
	{
        $errors = ['group_sections' => 'This value is not valid.'];
        $repository = $this->loadFixtures([
            LoadGroupSectionData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $section = $repository->getReference('group_3_section_1');
        $params = [
			'content' => 'some text',
            'group_sections' => [$section->getId()],
		];
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Token'=>'user1'], json_encode($params));
        $this->assertResponseHasErrors($client->getResponse(), $errors);
    }

    public function getValidAnnouncementCredentialsForUpdateRequest()
    {
        return [
            'owner' => [[], 'user1', 'group_1'],
            'manager' => [[LoadGroupManagerData::class], 'user3', 'group_1'],
            'representative' => [[LoadGroupRepresentativesData::class], 'user3', 'group_1'],
        ];
    }
}
