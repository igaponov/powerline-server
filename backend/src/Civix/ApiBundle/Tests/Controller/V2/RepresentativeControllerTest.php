<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadRepresentativeData;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RepresentativeControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/representatives';

    /**
     * @var null|Client
     */
    private $client = null;

    public function setUp()
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    public function testDeleteGroupAvatarWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativeData::class,
        ])->getReferenceRepository();
        $representative = $repository->getReference('representative_jb');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user2"'];
        $client->request('DELETE', self::API_ENDPOINT.'/'.$representative->getId().'/avatar', [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testDeleteRepresentativeAvatarIsOk()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var Representative $representative */
        $representative = $repository->getReference('representative_jb');
        $client = $this->client;
        $storage = $client->getContainer()->get('civix_core.storage.array');
        $file = new UploadedFile(__DIR__.'/../../data/image.png', uniqid());
        $storage->addFile($file, 'avatar_representative_fs', $representative->getAvatarFileName());
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user1"'];
        $client->request('DELETE', self::API_ENDPOINT.'/'.$representative->getId().'/avatar', [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $files = $storage->getFiles('avatar_representative_fs');
        $this->assertCount(1, $files);
        $newFile = reset($files);
        $this->assertNotEquals($file, $newFile);
    }
}