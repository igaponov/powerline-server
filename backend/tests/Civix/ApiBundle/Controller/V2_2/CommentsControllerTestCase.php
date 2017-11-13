<?php

namespace Tests\Civix\ApiBundle\Controller\V2_2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\CommentedInterface;
use Civix\CoreBundle\Entity\User;
use Symfony\Component\BrowserKit\Client;

abstract class CommentsControllerTestCase extends WebTestCase
{
    use CommentControllerTestTrait;

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

    abstract protected function getEndpoint();

    protected function getComments(CommentedInterface $entity, User $user, array $comments): void
    {
        $uri = str_replace('{id}', $entity->getId(), $this->getEndpoint());
        $this->client->request('GET', $uri, [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$user->getToken()]);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        /** @var array $data */
        $data = json_decode($response->getContent(), true);
        $this->assertCount(count($comments), $data);
        foreach ($data as $key => $item) {
            $this->assertComment($comments[$key], $user, $item);
        }
    }

    public function getCommentsWithCursor(CommentedInterface $entity, User $user, BaseComment $comment)
    {
        $uri = str_replace('{id}', $entity->getId(), $this->getEndpoint());
        $this->client->request('GET', $uri, ['cursor' => $comment->getId()], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$user->getToken()]);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        /** @var array $data */
        $data = json_decode($response->getContent(), true);
        $this->assertCount(1, $data);
        $this->assertComment($comment, $user, $data[0]);
    }
}