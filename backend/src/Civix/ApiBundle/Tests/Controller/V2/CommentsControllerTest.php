<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\CommentedInterface;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Client;

abstract class CommentsControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client = null;

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

    public function getComments(CommentedInterface $entity, $count)
    {
        $client = $this->client;
        $uri = str_replace('{id}', $entity->getId(), $this->getApiEndpoint());
        $client->request('GET', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame($count, $data['totalItems']);
        $this->assertCount($count, $data['payload']);
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
        $params = ['comment_body' => 'comment text @user2', 'parent_comment' => $comment->getId()];
        $client->request('POST', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user3"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($params['comment_body'], $data['comment_body']);
        $this->assertEquals($params['parent_comment'], $data['parent_comment']);
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.dbal.default_connection');
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM social_activities WHERE type LIKE ?', ["follow-%Commented"]);
        $this->assertEquals(1, $count);
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM social_activities WHERE type LIKE ?', ["comment-replied"]);
        $this->assertEquals(1, $count);
        $this->assertRegExp('{comment text <a data-user-id="\d+">@user2</a>}', $data['comment_body_html']);
    }
}