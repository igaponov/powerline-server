<?php

namespace Tests\Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadTagData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Doctrine\Common\DataFixtures\ReferenceRepository;

class TagControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/groups/{group}/tags';

    public function testGetTags(): ReferenceRepository
    {
        $repository = $this->loadFixtures([
            LoadTagData::class,
            LoadGroupManagerData::class,
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $tags = [
            $repository->getReference('group_tag_1'),
            $repository->getReference('group_tag_2'),
            $repository->getReference('group_tag_3'),
            $repository->getReference('group_tag_4'),
            $repository->getReference('group_tag_5'),
        ];$client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $group = $repository->getReference('group_1');
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_AUTHORIZATION' => 'Bearer user4']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        /** @var array $data */
        $data = json_decode($response->getContent(), true);
        $this->assertCount(5, $data);
        foreach ($data as $key => $item) {
            $this->assertSame($tags[$key]->getId(), $item['id']);
            $this->assertSame($tags[$key]->getName(), $item['name']);
        }

        return $repository;
    }

    /**
     * @depends testGetTags
     * @param ReferenceRepository $repository
     */
    public function testAddTooManyTagsThrowsError(ReferenceRepository $repository): void
    {
        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $group = $repository->getReference('group_1');
        $tag = $repository->getReference('group_tag_1');
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT).'/'.$tag->getId();
        $client->request('PUT', $uri, [], [], ['HTTP_AUTHORIZATION' => 'Bearer user1']);
        $response = $client->getResponse();
        $this->assertEquals(500, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @depends testGetTags
     * @param ReferenceRepository $repository
     */
    public function testAddTag(ReferenceRepository $repository): void
    {
        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $group = $repository->getReference('group_3');
        $tag = $repository->getReference('group_tag_1');
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT).'/'.$tag->getId();
        $client->request('PUT', $uri, [], [], ['HTTP_AUTHORIZATION' => 'Bearer user3']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertCount(1, $group->getTags());
    }
}
