<?php

namespace Tests\Civix\ApiBundle\Controller\V2_2;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Symfony\Bundle\FrameworkBundle\Client;
use Tests\Civix\CoreBundle\DataFixtures\ORM\LoadPostCommentData;
use Tests\Civix\CoreBundle\DataFixtures\ORM\LoadPostCommentRateData;

class PostCommentsControllerTest extends CommentControllerTestCase
{
    const API_ENDPOINT = '/api/v2.2/posts/{id}/comments';

    /**
     * @var null|Client
     */
    private $client;

    public function setUp(): void
    {
        // Creates a initial client
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    public function tearDown(): void
    {
        $this->client = NULL;
        parent::tearDown();
    }

    /**
     * @QueryCount(5)
     */
    public function testGetComments(): ReferenceRepository
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
            LoadPostCommentRateData::class,
        ])->getReferenceRepository();
        $post = $repository->getReference('post_1');
        /** @var User $user */
        $user = $repository->getReference('user_1');
        /** @var BaseComment[] $comments */
        $comments = [
            $repository->getReference('post_comment_1'),
            $repository->getReference('post_comment_2'),
        ];
        $uri = str_replace('{id}', $post->getId(), self::API_ENDPOINT);
        $this->client->request('GET', $uri, [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$user->getToken()]);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        /** @var array $data */
        $data = json_decode($response->getContent(), true);
        $this->assertCount(count($comments), $data);
        foreach ($data as $key => $item) {
            $this->assertComment($comments[$key], $user, $item);
        }
        return $repository;
    }

    /**
     * @param ReferenceRepository $repository
     * @depends testGetComments
     * @QueryCount(5)
     */
    public function testGetCommentsWithCursor(ReferenceRepository $repository)
    {
        $post = $repository->getReference('post_1');
        /** @var User $user */
        $user = $repository->getReference('user_1');
        /** @var BaseComment $comment */
        $comment = $repository->getReference('post_comment_2');
        $uri = str_replace('{id}', $post->getId(), self::API_ENDPOINT);
        $this->client->request('GET', $uri, ['cursor' => $comment->getId()], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$user->getToken()]);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        /** @var array $data */
        $data = json_decode($response->getContent(), true);
        $this->assertCount(1, $data);
        $this->assertComment($comment, $user, $data[0]);
    }
}
