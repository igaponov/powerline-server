<?php

namespace Tests\Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadLinkData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;

class LinkControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/groups/{group}/links';

    public function testActions(): void
    {
        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $repository = $this->loadFixtures([
            LoadLinkData::class,
            LoadGroupManagerData::class,
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $params = [
            'url' => 'http://example.com',
            'label' => 'Test Link',
        ];
        $client->request('POST', $uri, [], [], ['HTTP_AUTHORIZATION' => 'Bearer user2'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(3, $data);
        $this->assertNotEmpty($data['id']);
        $this->assertSame($params['url'], $data['url']);
        $this->assertSame($params['label'], $data['label']);
        // test GET
        $client->request('GET', $uri, [], [], ['HTTP_AUTHORIZATION' => 'Bearer user4']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        /** @var array $data */
        $data = json_decode($response->getContent(), true);
        $this->assertCount(4, $data);
        foreach ($data as $item) {
            $this->assertCount(3, $item);
        }
    }
}
