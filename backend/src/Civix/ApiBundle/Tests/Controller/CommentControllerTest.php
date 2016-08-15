<?php

namespace Civix\ApiBundle\Tests\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionCommentData;
use Symfony\Bundle\FrameworkBundle\Client;

class CommentControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    private $client = null;

    public function setUp()
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    public function tearDown()
    {
        $this->client = NULL;
        parent::tearDown();
    }

    public function testGetPollComments()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('group_question_1');
        $client = $this->client;
        $uri = str_replace('{id}', $entity->getId(), '/api/poll/{id}/comments/');
        $client->request('GET', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(3, $data);
    }

    public function testGetPostComments()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('post_5');
        $client = $this->client;
        $uri = str_replace('{id}', $entity->getId(), '/api/post/{id}/comments/');
        $client->request('GET', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testGetUserPetitionComments()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('user_petition_5');
        $client = $this->client;
        $uri = str_replace('{id}', $entity->getId(), '/api/micro-petitions/{id}/comments/');
        $client->request('GET', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testCreatePollComment()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('group_question_1');
        $comment = $repository->getReference('question_comment_1');
        $client = $this->client;
        $uri = str_replace('{id}', $entity->getId(), '/api/poll/{id}/comments/');
        $params = ['comment_body' => 'comment text', 'parent_comment' => $comment->getId()];
        $client->request('POST', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($params['comment_body'], $data['comment_body']);
        $this->assertEquals($params['parent_comment'], $data['parent_comment']);
    }

    public function testCreatePostComment()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('post_1');
        $comment = $repository->getReference('post_comment_1');
        $client = $this->client;
        $uri = str_replace('{id}', $entity->getId(), '/api/post/{id}/comments/');
        $params = ['comment_body' => 'comment text', 'parent_comment' => $comment->getId()];
        $client->request('POST', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($params['comment_body'], $data['comment_body']);
        $this->assertEquals($params['parent_comment'], $data['parent_comment']);
    }

    public function testCreateUserPetitionComment()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('user_petition_1');
        $comment = $repository->getReference('petition_comment_1');
        $client = $this->client;
        $uri = str_replace('{id}', $entity->getId(), '/api/micro-petitions/{id}/comments/');
        $params = ['comment_body' => 'comment text', 'parent_comment' => $comment->getId()];
        $client->request('POST', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($params['comment_body'], $data['comment_body']);
        $this->assertEquals($params['parent_comment'], $data['parent_comment']);
    }

    public function testUpdatePollComment()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('group_question_1');
        $comment = $repository->getReference('question_comment_1');
        $client = $this->client;
        $uri = str_replace(['{entity}', '{id}'], [$entity->getId(), $comment->getId()], '/api/poll/{entity}/comments/{id}');
        $params = ['comment_body' => 'comment text', 'privacy' => BaseComment::PRIVACY_PRIVATE];
        $client->request('PUT', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($params['comment_body'], $data['comment_body']);
        $this->assertEquals($params['privacy'], $data['privacy']);
    }

    public function testUpdatePostComment()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('post_1');
        $comment = $repository->getReference('post_comment_1');
        $client = $this->client;
        $uri = str_replace(['{entity}', '{id}'], [$entity->getId(), $comment->getId()], '/api/post/{entity}/comments/{id}');
        $params = ['comment_body' => 'comment text', 'privacy' => BaseComment::PRIVACY_PRIVATE];
        $client->request('PUT', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($params['comment_body'], $data['comment_body']);
        $this->assertEquals($params['privacy'], $data['privacy']);
    }

    public function testUpdateUserPetitionComment()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('user_petition_1');
        $comment = $repository->getReference('petition_comment_1');
        $client = $this->client;
        $uri = str_replace(['{entity}', '{id}'], [$entity->getId(), $comment->getId()], '/api/micro-petitions/{entity}/comments/{id}');
        $params = ['comment_body' => 'comment text', 'privacy' => BaseComment::PRIVACY_PRIVATE];
        $client->request('PUT', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($params['comment_body'], $data['comment_body']);
        $this->assertEquals($params['privacy'], $data['privacy']);
    }

    public function testDeletePollComment()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('group_question_1');
        $comment = $repository->getReference('question_comment_1');
        $client = $this->client;
        $uri = str_replace(['{entity}', '{id}'], [$entity->getId(), $comment->getId()], '/api/poll/{entity}/comments/{id}');
        $params = ['comment_body' => 'comment text', 'privacy' => BaseComment::PRIVACY_PRIVATE];
        $client->request('DELETE', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Deleted by author', $data['comment_body']);
    }

    public function testDeletePostComment()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('post_1');
        $comment = $repository->getReference('post_comment_1');
        $client = $this->client;
        $uri = str_replace(['{entity}', '{id}'], [$entity->getId(), $comment->getId()], '/api/post/{entity}/comments/{id}');
        $params = ['comment_body' => 'comment text', 'privacy' => BaseComment::PRIVACY_PRIVATE];
        $client->request('DELETE', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Deleted by author', $data['comment_body']);
    }

    public function testDeleteUserPetitionComment()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('user_petition_1');
        $comment = $repository->getReference('petition_comment_1');
        $client = $this->client;
        $uri = str_replace(['{entity}', '{id}'], [$entity->getId(), $comment->getId()], '/api/micro-petitions/{entity}/comments/{id}');
        $client->request('DELETE', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Deleted by author', $data['comment_body']);
    }
}