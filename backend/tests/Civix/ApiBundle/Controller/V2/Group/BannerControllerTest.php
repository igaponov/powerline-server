<?php

namespace Tests\Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Doctrine\Common\DataFixtures\ReferenceRepository;

class BannerControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/groups/{group}/banner';

    public function testCreateBanner(): ReferenceRepository
    {
        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference('group_3');
        $this->assertNull($group->getBanner()->getName());
        $params = [
            'banner' => base64_encode(file_get_contents(__DIR__.'/../../../../../data/image.png'))
        ];
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer user3'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $banner = $data['banner'];
        $this->assertRegExp('/[\w\d]+\.png/', $banner);
        $storage = $client->getContainer()->get('civix_core.storage.array');
        $this->assertCount(1, $storage->getFiles('banner_group_fs'));

        return $repository;
    }

    /**
     * @depends testCreateBanner
     * @param ReferenceRepository $repository
     */
    public function testUpdateBanner(ReferenceRepository $repository): void
    {
        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        /** @var Group $group */
        $group = $repository->getReference('group_3');
        $params = [
            'banner' => base64_encode(file_get_contents(__DIR__.'/../../../../../data/image2.png'))
        ];
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer user3'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertRegExp('/[\w\d]+\.png/', $data['banner']);
        $this->assertNotEquals($group->getBanner()->getName(), $data['banner']);
        $storage = $client->getContainer()->get('civix_core.storage.array');
        $this->assertCount(1, $storage->getFiles('banner_group_fs'));
    }
}
