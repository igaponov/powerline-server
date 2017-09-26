<?php

namespace Tests\Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostVoteData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;

class UserSharedPostControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/user/shared-posts';

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::bootFixtureLoader();
        self::$fixtureLoader->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadPostVoteData::class,
        ]);
    }

    public function testSharePost()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_2');
        /** @var Post $post */
        $post = $repository->getReference('post_5');
        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization' => 'Bearer user2']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertNotNull($user->getLastPostSharedAt());
    }

    public function testSharePostAfterLessThan72HoursReturnsError()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var Post $post */
        $post = $repository->getReference('post_3');
        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization' => 'Bearer user3']
        );
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('User can share a post only once in 72 hours.', $data['message']);
    }

    public function testSharePostThatNotVotedReturnsError()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var Post $post */
        $post = $repository->getReference('post_5');
        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization' => 'Bearer user3']
        );
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('User can share only a post he has upvoted.', $data['message']);
    }

    public function testSharePostByOwnerReturnsError()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var Post $post */
        $post = $repository->getReference('post_1');
        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization' => 'Bearer user1']
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }
}
