<?php

namespace Tests\Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionSignatureData;

class UserSharedUserPetitionControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/user/shared-user-petitions';

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::bootFixtureLoader();
        self::$fixtureLoader->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadUserPetitionSignatureData::class,
        ]);
    }

    public function testSharePetition()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_2');
        /** @var Post $post */
        $post = $repository->getReference('user_petition_5');
        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization' => 'Bearer user2']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertNotNull($user->getLastContentSharedAt());
    }

    public function testSharePetitionAfterLessThan1HourReturnsError()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_3');
        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization' => 'Bearer user3']
        );
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('User can share a petition only once in 1 hour.', $data['message']);
    }

    public function testSharePetitionThatNotVotedReturnsError()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_5');
        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization' => 'Bearer user3']
        );
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('User can share only a petition he has signed.', $data['message']);
    }

    public function testSharePetitionByOwnerReturnsError()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var Post $post */
        $post = $repository->getReference('user_petition_1');
        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization' => 'Bearer user1']
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }
}
