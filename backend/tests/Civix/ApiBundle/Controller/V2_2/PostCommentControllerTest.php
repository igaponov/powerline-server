<?php

namespace Tests\Civix\ApiBundle\Controller\V2_2;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Symfony\Bundle\FrameworkBundle\Client;
use Tests\Civix\CoreBundle\DataFixtures\ORM\LoadPostCommentData;
use Tests\Civix\CoreBundle\DataFixtures\ORM\LoadPostCommentRateData;

class PostCommentControllerTest extends CommentControllerTestCase
{
    const API_ENDPOINT = '/api/v2.2/post-comments/{id}';

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
     * @QueryCount(3)
     */
    public function testGetChildComments(): ReferenceRepository
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
            LoadPostCommentRateData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('post_comment_1');
        /** @var User $user */
        $user = $repository->getReference('user_1');
        /** @var BaseComment[] $comments */
        $comments = [
            $repository->getReference('post_comment_4'),
            $repository->getReference('post_comment_5'),
            $repository->getReference('post_comment_6'),
        ];
        $this->client->enableProfiler();
        $uri = str_replace('{id}', $comment->getId(), self::API_ENDPOINT.'/comments');
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
     * @depends testGetChildComments
     * @QueryCount(3)
     */
    public function testGetChildCommentsWithCursor(ReferenceRepository $repository): void
    {
        $parent = $repository->getReference('post_comment_1');
        /** @var User $user */
        $user = $repository->getReference('user_1');
        /** @var BaseComment $comment */
        $comment = $repository->getReference('post_comment_5');
        /** @var BaseComment $cursor */
        $cursor = $repository->getReference('post_comment_6');
        $this->client->enableProfiler();
        $uri = str_replace('{id}', $parent->getId(), self::API_ENDPOINT.'/comments');
        $this->client->request('GET', $uri, ['cursor' => $comment->getId(), 'limit' => 1], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$user->getToken()]);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertContains($uri.'?cursor='.$cursor->getId().'&limit=1', $response->headers->get('X-Cursor-Next'));
        $data = json_decode($response->getContent(), true);
        $this->assertCount(1, $data);
        $this->assertComment($comment, $user, $data[0]);
    }
}
