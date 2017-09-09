<?php

namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\BaseCommentRate;
use Civix\CoreBundle\Entity\Karma;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;

abstract class CommentControllerTestCase extends WebTestCase
{
    /**
     * @var Client
     */
    private $client = null;

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

    protected function updateComment(BaseComment $comment)
    {
        $client = $this->client;
        $uri = str_replace('{id}', $comment->getId(), $this->getApiEndpoint());
        $params = ['comment_body' => 'comment text', 'privacy' => 'private'];
        $client->request('PUT', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($params['comment_body'], $data['comment_body']);
        $this->assertEquals($params['comment_body'], $data['comment_body_html']);
        /** @var BaseComment $comment */
        $comment = $client->getContainer()->get('doctrine')->getManager()->merge($comment);
        $this->assertEquals($params['comment_body'], $comment->getCommentBodyHtml());
        $this->assertEquals($params['privacy'], $data['privacy']);
    }

    protected function updateCommentWithWrongCredentials(BaseComment $comment)
    {
        $client = $this->client;
        $uri = str_replace('{id}', $comment->getId(), $this->getApiEndpoint());
        $params = ['comment_body' => 'comment text', 'privacy' => 'private'];
        $client->request('PUT', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user4"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    protected function deleteComment(BaseComment $comment)
    {
        $client = $this->client;
        $uri = str_replace('{id}', $comment->getId(), $this->getApiEndpoint());
        $params = ['comment_body' => 'comment text'];
        $client->request('DELETE', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var EntityManager $em */
        $em = $client->getContainer()->get('doctrine')->getManager();
        /** @var BaseComment $comment */
        $comment = $em->merge($comment);
        $this->assertEquals('Deleted by author', $comment->getCommentBody());
        $this->assertEquals('Deleted by author', $comment->getCommentBodyHtml());
    }

    protected function deleteCommentWithWrongCredentials(BaseComment $comment)
    {
        $client = $this->client;
        $uri = str_replace('{id}', $comment->getId(), $this->getApiEndpoint());
        $client->request('DELETE', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user4"']
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    protected function rateCommentWithWrongCredentials(BaseComment $comment)
    {
        $client = $this->client;
        $uri = str_replace('{id}', $comment->getId(), $this->getApiEndpoint().'/rate');
        $client->request('POST', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user4"']
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    protected function rateComment(BaseComment $comment, $rate, $user)
    {
        $client = $this->client;
        $uri = str_replace('{id}', $comment->getId(), $this->getApiEndpoint().'/rate');
        $params = ['rate_value' => $rate];
        $client->request('POST', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $rate = array_search($rate, BaseCommentRate::getRateValueLabels());
        $this->assertEquals(
            $rate,
            $data['rate_sum']
        );
        $this->assertEquals($rate === BaseCommentRate::RATE_DELETE ? 2 : 3, $data['rates_count']);
        $result = $client->getContainer()->get('doctrine.dbal.default_connection')
            ->fetchAssoc('SELECT * FROM karma');
        if ($rate == BaseCommentRate::RATE_UP) {
            $this->assertArraySubset([
                'user_id' => $comment->getUser()->getId(),
                'type' => Karma::TYPE_RECEIVE_UPVOTE_ON_COMMENT,
                'points' => 2,
                'metadata' => serialize([
                    'type' => $comment->getEntityType(),
                    'comment_id' => $comment->getId(),
                    'rate_id' => $comment->getRates()->last()->getId(),
                ]),
            ], $result);
        } else {
            $this->assertFalse((bool) $result);
        }
    }

    protected function updateCommentRate(BaseComment $comment, $rate)
    {
        $client = $this->client;
        $uri = str_replace('{id}', $comment->getId(), $this->getApiEndpoint().'/rate');
        $params = ['rate_value' => $rate];
        $client->request('POST', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $rate = array_search($rate, BaseCommentRate::getRateValueLabels());
        $results = $client->getContainer()->get('doctrine.dbal.default_connection')
            ->fetchAll('SELECT * FROM karma');
        if ($rate == BaseCommentRate::RATE_UP) {
            $this->assertCount(1, $results);
            $this->assertArraySubset([
                'user_id' => $comment->getUser()->getId(),
                'type' => Karma::TYPE_RECEIVE_UPVOTE_ON_COMMENT,
                'points' => 2,
                'metadata' => serialize([
                    'type' => $comment->getEntityType(),
                    'comment_id' => $comment->getId(),
                    'rate_id' => $comment->getRates()->first()->getId(),
                ]),
            ], $results[0]);
        } else {
            $this->assertEquals(
                $rate,
                $data['rate_sum']
            );
            $this->assertEquals($rate === BaseCommentRate::RATE_DELETE ? 0 : 1, $data['rates_count']);
            $this->assertCount(0, $results);
        }
    }

    public function getRates()
    {
        return [
            'up' => ['up', 'user3'],
            'down' => ['down', 'user4'],
            'delete' => ['delete', 'user3'],
        ];
    }
}