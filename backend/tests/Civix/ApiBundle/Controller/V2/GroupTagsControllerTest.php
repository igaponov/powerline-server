<?php

namespace Tests\Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadTagData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;

class GroupTagsControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/group-tags';

    public function testSearchTags(): void
    {
        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadTagData::class,
        ])->getReferenceRepository();
        $tags = [
            $repository->getReference('group_tag_1'),
            $repository->getReference('group_tag_4'),
        ];
        $server = ['HTTP_AUTHORIZATION' => 'Bearer user1'];
        $client->request('GET', self::API_ENDPOINT, ['name' => '%test'], [], $server);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        /** @var array $data */
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data);
        foreach ($data as $key => $item) {
            $this->assertCount(2, $item);
            $this->assertSame($tags[$key]->getId(), $item['id']);
            $this->assertSame($tags[$key]->getName(), $item['name']);
        }
    }

    /**
     * @depends testSearchTags
     */
    public function testCreateDuplicateTagReturnsErrors(): void
    {
        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $server = ['HTTP_AUTHORIZATION' => 'Bearer user1'];
        $params = ['name' => 'test tag'];
        $client->request('POST', self::API_ENDPOINT, [], [], $server, json_encode($params));
        $errors = [
            'name' => 'This value is already used.',
        ];
        $this->assertResponseHasErrors($client->getResponse(), $errors);
    }

    /**
     * @depends testSearchTags
     */
    public function testCreateTag(): void
    {
        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $server = ['HTTP_AUTHORIZATION' => 'Bearer user1'];
        $params = ['name' => 'new tag'];
        $client->request('POST', self::API_ENDPOINT, [], [], $server, json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        /** @var array $data */
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data);
        $this->assertNotEmpty($data['id']);
        $this->assertSame($params['name'], $data['name']);
    }
}
