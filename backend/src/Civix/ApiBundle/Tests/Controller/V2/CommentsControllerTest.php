<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\CommentedInterface;
use Symfony\Bundle\FrameworkBundle\Client;

abstract class CommentsControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    abstract protected function getApiEndpoint();

    public function setUp()
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    public function tearDown()
    {
        $this->client = NULL;
        parent::tearDown();
    }

    public function getComments(CommentedInterface $entity, array $comments, array $params = [])
    {
        $client = $this->client;
        $uri = str_replace('{id}', $entity->getId(), $this->getApiEndpoint());
        $client->request('GET', $uri, $params, [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertCount($data['totalItems'], $comments);
        /** @var array $payload */
        $payload = $data['payload'];
        $this->assertCount(count($comments), $payload);
        foreach ($payload as $k => $item) {
            $this->assertEquals($comments[$k]->getId(), $item['id']);
        }
    }

    public function getChildComments(BaseComment $comment, $count)
    {
        $client = $this->client;
        $entity = $comment->getCommentedEntity();
        $uri = str_replace('{id}', $entity->getId(), $this->getApiEndpoint());
        $client->request('GET', $uri, ['parent' => $comment->getId()], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame($count, $data['totalItems']);
        $this->assertCount($count, $data['payload']);
        $this->assertFalse($data['payload'][0]['is_root']);
    }

    public function getCommentsWithInvalidCredentials(CommentedInterface $entity)
    {
        $client = $this->client;
        $uri = str_replace('{id}', $entity->getId(), $this->getApiEndpoint());
        $client->request('GET', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user4"']
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function createComment(CommentedInterface $entity, BaseComment $comment)
    {
        $client = $this->client;
        $uri = str_replace('{id}', $entity->getId(), $this->getApiEndpoint());
        $params = [
            'comment_body' => 'comment text @user2',
            'parent_comment' => $comment->getId(),
            'privacy' => 'private',
            'is_root' => true,
        ];
        $client->request('POST', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user3"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($params['comment_body'], $data['comment_body']);
        $this->assertEquals($params['parent_comment'], $data['parent_comment']);
        $this->assertEquals($params['privacy'], $data['privacy']);
        $this->assertRegExp('{comment text <a data-user-id="\d+">@user2</a>}', $data['comment_body_html']);
    }

    public function createRootComment(CommentedInterface $entity)
    {
        $client = $this->client;
        $uri = str_replace('{id}', $entity->getId(), $this->getApiEndpoint());
        $params = [
            'comment_body' => 'comment text @user2',
            'privacy' => 'private',
        ];
        $client->request('POST', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user3"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($params['comment_body'], $data['comment_body']);
        $this->assertEquals(0, $data['parent_comment']);
        $this->assertEquals($params['privacy'], $data['privacy']);
        $this->assertRegExp('{comment text <a data-user-id="\d+">@user2</a>}', $data['comment_body_html']);
    }
}