<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\CommentedInterface;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Test\SocialActivityTester;
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
        $params = [
            'comment_body' => 'comment text @user2',
            'parent_comment' => $comment->getId(),
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
        $this->assertEquals($params['parent_comment'], $data['parent_comment']);
        $this->assertEquals($params['privacy'], $data['privacy']);
        /** @var Connection $conn */
        if ($entity instanceof Question) {
            $ownType = SocialActivity::TYPE_OWN_POLL_COMMENTED;
            $followType = SocialActivity::TYPE_FOLLOW_POLL_COMMENTED;
        } elseif ($entity instanceof Post) {
            $ownType = SocialActivity::TYPE_OWN_POST_COMMENTED;
            $followType = SocialActivity::TYPE_FOLLOW_POST_COMMENTED;
        } else {
            $ownType = SocialActivity::TYPE_OWN_USER_PETITION_COMMENTED;
            $followType = SocialActivity::TYPE_FOLLOW_USER_PETITION_COMMENTED;
        }
        $this->assertRegExp('{comment text <a data-user-id="\d+">@user2</a>}', $data['comment_body_html']);
        $tester = new SocialActivityTester($client->getContainer()->get('doctrine')->getManager());
        $tester->assertActivitiesCount(4);
        $tester->assertActivity(SocialActivity::TYPE_COMMENT_MENTIONED, $comment->getUser()->getId());
        $tester->assertActivity(SocialActivity::TYPE_COMMENT_REPLIED, $comment->getUser()->getId());
        $tester->assertActivity($ownType, $entity->getUser()->getId());
        $tester->assertActivity($followType, null, $comment->getChildrenComments()->first()->getUser()->getId());
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(4, $queue->count());
        $this->assertEquals(4, $queue->hasMessageWithMethod('sendSocialActivity'));
    }

    /**
     * @param CommentedInterface $entity
     * @param BaseComment $comment
     * @param User[] $users
     */
    public function createCommentNotifyEveryone(CommentedInterface $entity, BaseComment $comment, $users)
    {
        $client = $this->client;
        $uri = str_replace('{id}', $entity->getId(), $this->getApiEndpoint());
        $params = [
            'comment_body' => 'comment text @everyone',
            'parent_comment' => $comment->getId(),
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
        $this->assertEquals($params['comment_body'], $data['comment_body_html']);
        $this->assertEquals($params['parent_comment'], $data['parent_comment']);
        $this->assertEquals($params['privacy'], $data['privacy']);
        /** @var Connection $conn */
        if ($entity instanceof Question) {
            $ownType = SocialActivity::TYPE_OWN_POLL_COMMENTED;
            $followType = SocialActivity::TYPE_FOLLOW_POLL_COMMENTED;
        } elseif ($entity instanceof Post) {
            $ownType = SocialActivity::TYPE_OWN_POST_COMMENTED;
            $followType = SocialActivity::TYPE_FOLLOW_POST_COMMENTED;
        } else {
            $ownType = SocialActivity::TYPE_OWN_USER_PETITION_COMMENTED;
            $followType = SocialActivity::TYPE_FOLLOW_USER_PETITION_COMMENTED;
        }
        /** @var Connection $conn */
        $tester = new SocialActivityTester($client->getContainer()->get('doctrine')->getManager());
        $tester->assertActivitiesCount(6);
        foreach ($users as $user) {
            $tester->assertActivity(SocialActivity::TYPE_COMMENT_MENTIONED, $user->getId());
        }
        $tester->assertActivity(SocialActivity::TYPE_COMMENT_REPLIED, $comment->getUser()->getId());
        $tester->assertActivity($ownType, $entity->getUser()->getId());
        $tester->assertActivity($followType, null, $comment->getChildrenComments()->first()->getUser()->getId());
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(6, $queue->count());
        $this->assertEquals(6, $queue->hasMessageWithMethod('sendSocialActivity'));
    }

    public function createCommentWithEveryoneByMemberNotifyNobody(CommentedInterface $entity, BaseComment $comment)
    {
        $client = $this->client;
        $uri = str_replace('{id}', $entity->getId(), $this->getApiEndpoint());
        $params = [
            'comment_body' => 'comment text @everyone',
            'parent_comment' => $comment->getId(),
            'privacy' => 'private',
        ];
        $client->request('POST', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user4"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($params['comment_body'], $data['comment_body']);
        $this->assertEquals($params['comment_body'], $data['comment_body_html']);
        $this->assertEquals($params['parent_comment'], $data['parent_comment']);
        $this->assertEquals($params['privacy'], $data['privacy']);
        /** @var Connection $conn */
        if ($entity instanceof Question) {
            $ownType = SocialActivity::TYPE_OWN_POLL_COMMENTED;
            $followType = SocialActivity::TYPE_FOLLOW_POLL_COMMENTED;
        } elseif ($entity instanceof Post) {
            $ownType = SocialActivity::TYPE_OWN_POST_COMMENTED;
            $followType = SocialActivity::TYPE_FOLLOW_POST_COMMENTED;
        } else {
            $ownType = SocialActivity::TYPE_OWN_USER_PETITION_COMMENTED;
            $followType = SocialActivity::TYPE_FOLLOW_USER_PETITION_COMMENTED;
        }
        /** @var Connection $conn */
        $tester = new SocialActivityTester($client->getContainer()->get('doctrine')->getManager());
        $tester->assertActivitiesCount(3);
        $tester->assertActivity(SocialActivity::TYPE_COMMENT_REPLIED, $comment->getUser()->getId());
        $tester->assertActivity($ownType, $entity->getUser()->getId());
        $tester->assertActivity($followType, null, $comment->getChildrenComments()->last()->getUser()->getId());
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(3, $queue->count());
        $this->assertEquals(3, $queue->hasMessageWithMethod('sendSocialActivity'));
    }
}