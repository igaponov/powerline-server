<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadEducationalContextData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Symfony\Bundle\FrameworkBundle\Client;

class EducationalContextControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/educational-contexts';

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Client
     */
    private $client = null;

    public function setUp()
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);

        $this->em = $this->getContainer()->get('doctrine')->getManager();
    }

    public function tearDown()
    {
        $this->client = NULL;
        $this->em = null;
        parent::tearDown();
    }
    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidEducationalContextCredentialsForUpdateRequest
     */
    public function testDeleteEducationalContextWithWrongCredentialsReturnsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadEducationalContextData::class,
        ])->getReferenceRepository();
        $context = $repository->getReference($reference);
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$context->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidEducationalContextCredentialsForUpdateRequest
     */
    public function testDeleteEducationalContextIsOk($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadGroupManagerData::class,
            LoadEducationalContextData::class,
        ])->getReferenceRepository();
        $context = $repository->getReference($reference);
        $client = $this->client;
        $storage = $this->getContainer()->get('civix_core.storage.array');
        $this->assertCount(1, $storage->getFiles('educational_image_fs'));
        $client->getContainer()->set('civix_core.storage.array', $storage);
        $client->request('DELETE', self::API_ENDPOINT.'/'.$context->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertCount(0, $storage->getFiles('educational_image_fs'));
    }

    public function getInvalidEducationalContextCredentialsForUpdateRequest()
    {
        return [
            'member' => ['user4', 'educational_context_1'],
            'outlier' => ['user1', 'educational_context_4'],
        ];
    }

    public function getValidEducationalContextCredentialsForUpdateRequest()
    {
        return [
            'owner' => ['user1', 'educational_context_3'],
            'manager' => ['user2', 'educational_context_3'],
        ];
    }
}