<?php

namespace Tests\Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Group\Link;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadLinkData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;

class GroupLinksControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/group-links/';

    public function testActions(): void
    {
        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $repository = $this->loadFixtures([
            LoadLinkData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference('group_1');
        /** @var Link $link */
        $link = $repository->getReference('group_link_1');
        $uri = self::API_ENDPOINT.$link->getId();
        $params = [
            'url' => 'http://example.com',
            'label' => 'Test Link',
        ];
        $server = ['HTTP_AUTHORIZATION' => 'Bearer user2'];
        $client->request('PUT', $uri, [], [], $server, json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(3, $data);
        $this->assertSame($link->getId(), $data['id']);
        $this->assertSame($params['url'], $data['url']);
        $this->assertSame($params['label'], $data['label']);
        // test DELETE
        $client->request('DELETE', $uri, [], [], $server);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertSame(2, $group->getLinks()->count());
    }
}
