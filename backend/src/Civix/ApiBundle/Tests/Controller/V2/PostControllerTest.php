<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Service\PostManager;
use Civix\CoreBundle\Test\SocialActivityTester;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostHashTagData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostSubscriberData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostVoteData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Faker\Factory;
use Civix\ApiBundle\Tests\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class PostControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/posts';

    /**
     * @var null|Client
     */
    private $client = null;

    public function setUp()
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    protected function tearDown()
    {
        $this->client = null;
        parent::tearDown();
    }

    public function testGetPosts()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadPostData::class,
            LoadPostVoteData::class,
        ])->getReferenceRepository();
        $post = $repository->getReference('post_1');
        $answer = $repository->getReference('post_answer_1');
        $client = $this->client;
        $client->request('GET',
            self::API_ENDPOINT, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(6, $data['totalItems']);
        $this->assertCount(6, $data['payload']);
        foreach ($data['payload'] as $item) {
            if ($post->getId() == $item['id']) {
                $this->assertCount(1, $item['votes']);
                $this->assertEquals($answer->getOption(), $item['votes'][0]['option']);
                $this->assertArrayHasKey('html_body', $item);
            }
        }
    }

    public function testGetPostsByTag()
    {
        $this->loadFixtures([
            LoadUserGroupData::class,
            LoadPostData::class,
        ]);
        $client = $this->client;
        $client->request('GET',
            self::API_ENDPOINT, ['tag' => 'hash_tag_name'], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(0, $data['totalItems']);
        $this->assertCount(0, $data['payload']);
    }

    public function testGetPost()
    {
        $repository = $this->loadFixtures([
            LoadPostData::class,
        ])->getReferenceRepository();
        /** @var Post $post */
        $post = $repository->getReference('post_1');
        $client = $this->client;
        $client->request('GET',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($post->getBody(), $data['body']);
        $this->assertSame($post->isSupportersWereInvited(), $data['supporters_were_invited']);
    }

    public function testGetDeletedPost()
    {
        $repository = $this->loadFixtures([
            LoadPostData::class,
        ])->getReferenceRepository();
        /** @var Post $post */
        $post = $repository->getReference('post_7');
        $client = $this->client;
        $client->request('GET',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(404, $response->getStatusCode(), $response->getContent());
    }

    public function testUpdatePostAccessDenied()
    {
        $repository = $this->loadFixtures([
            LoadPostData::class,
        ])->getReferenceRepository();
        /** @var Post $post */
        $post = $repository->getReference('post_1');
        $client = $this->client;
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"'],
            json_encode([])
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testUpdatePostReturnsErrors()
    {
        $repository = $this->loadFixtures([
            LoadPostData::class,
        ])->getReferenceRepository();
        /** @var Post $post */
        $post = $repository->getReference('post_1');
        $expectedErrors = [
            'body' => 'This value should not be blank.',
        ];
        $client = $this->client;
        $params = [
            'body' => '',
        ];
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Validation Failed', $data['message']);
        $errors = $data['errors'];
        foreach ($expectedErrors as $child => $error) {
            if (is_int($child)) {
                $this->assertContains($error, $errors['errors']);
            } elseif ($error) {
                $this->assertContains($error, $errors['children'][$child]['errors']);
            } else {
                $this->assertEmpty($errors['children'][$child]);
            }
        }
    }

    public function testUpdatePost()
    {
        $repository = $this->loadFixtures([
            LoadPostData::class,
        ])->getReferenceRepository();
        $faker = Factory::create();
        /** @var Post $post */
        $post = $repository->getReference('post_1');
        $client = $this->client;
        $hashTags = [
            '#testHashTag',
            '#powerlineHashTag',
        ];
        $params = [
            'body' => $faker->text."\n".implode(' ', $hashTags),
        ];
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($params['body'], $data['body']);
        // check addHashTags event listener
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')
            ->getConnection();
        $count = (int)$conn->fetchColumn('SELECT COUNT(*) FROM hash_tags');
        $this->assertCount($count, $hashTags);
        $this->assertCount($count, $data['cached_hash_tags']);
        // check activity
        $description = $conn->fetchColumn('SELECT description FROM activities WHERE post_id = ?', [$data['id']]);
        $this->assertSame($data['body'], $description);
    }

    public function testUpdatePostWithExistentHashTag()
    {
        $repository = $this->loadFixtures([
            LoadPostHashTagData::class,
        ])->getReferenceRepository();
        $faker = Factory::create();
        /** @var Post $post */
        $post = $repository->getReference('post_1');
        $client = $this->client;
        $hashTags = [
            '#testHashTag',
            '#powerlineHashTag',
        ];
        $params = [
            'body' => $faker->text."\n".implode(' ', $hashTags),
        ];
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        // check addHashTags event listener
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')
            ->getConnection();
        $count = (int)$conn->fetchColumn('SELECT COUNT(*) FROM hash_tags_posts WHERE post_id = ?', [$data['id']]);
        $this->assertCount($count, $hashTags);
        $this->assertCount($count, $data['cached_hash_tags']);
    }

    public function testBoostPostWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadPostData::class,
        ])->getReferenceRepository();
        /** @var Post $post */
        $post = $repository->getReference('post_2');
        $this->assertFalse($post->isBoosted());
        $client = $this->client;
        $client->request('PATCH',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testBoostPost()
    {
        $repository = $this->loadFixtures([
            LoadPostData::class,
        ])->getReferenceRepository();
        /** @var Post $post */
        $post = $repository->getReference('post_2');
        $this->assertFalse($post->isBoosted());
        $client = $this->client;
        $client->request('PATCH',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['boosted']);
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        // check activity
        $description = $conn->fetchColumn('SELECT description FROM activities WHERE post_id = ?', [$post->getId()]);
        $this->assertSame($post->getBody(), $description);
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(1, $queue->count());
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendBoostedPostPush', [$post->getGroup()->getId(), $post->getId()]));
    }

    public function testDeletePostAccessDenied()
    {
        $repository = $this->loadFixtures([
            LoadPostData::class,
        ])->getReferenceRepository();
        /** @var Post $post */
        $post = $repository->getReference('post_1');
        $client = $this->client;
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testDeletePost()
    {
        $repository = $this->loadFixtures([
            LoadPostData::class,
        ])->getReferenceRepository();
        /** @var Post $post */
        $post = $repository->getReference('post_1');
        $client = $this->client;
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')
            ->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM posts WHERE id = ?', [$post->getId()]);
        $this->assertEquals(0, $count);
    }

    public function testSignPostReturnsErrors()
    {
        $repository = $this->loadFixtures([
            LoadPostData::class,
        ])->getReferenceRepository();
        /** @var Post $post */
        $post = $repository->getReference('post_6');
        $expectedErrors = [
            'You could not answer to your post.',
            'option' => 'This value should not be blank.',
        ];
        $client = $this->client;
        $client->request('POST',
            self::API_ENDPOINT.'/'.$post->getId().'/vote', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user3"']
        );
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Validation Failed', $data['message']);
        $errors = $data['errors'];
        foreach ($expectedErrors as $child => $error) {
            if (is_int($child)) {
                $this->assertContains($error, $errors['errors']);
            } elseif ($error) {
                $this->assertContains($error, $errors['children'][$child]['errors']);
            } else {
                $this->assertEmpty($errors['children'][$child]);
            }
        }
    }

    public function testSignPost()
    {
        $repository = $this->loadFixtures([
            LoadPostData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $manager = $this->getPostManagerMock(['checkIfNeedBoost']);
        $manager->expects($this->once())
            ->method('checkIfNeedBoost')
            ->willReturn(true);
        $client->getContainer()->set('civix_core.post_manager', $manager);
        /** @var Post $post */
        $post = $repository->getReference('post_2');
        $client->request('POST',
            self::API_ENDPOINT.'/'.$post->getId().'/vote', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"'],
            json_encode(['option' => 'upvote'])
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(Post\Vote::OPTION_UPVOTE, $data['option']);
        /** @var EntityManager $em */
        $em = $client->getContainer()
            ->get('doctrine')->getManager();
        $conn = $em->getConnection();
        // check activity
        $description = $conn->fetchColumn('SELECT description FROM activities WHERE post_id = ?', [$post->getId()]);
        $this->assertSame($post->getBody(), $description);
        $tester = new SocialActivityTester($em);
        $tester->assertActivitiesCount(1);
        $tester->assertActivity(SocialActivity::TYPE_OWN_POST_VOTED, $post->getUser()->getId());
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(2, $queue->count());
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendSocialActivity'));
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendBoostedPostPush', [$post->getGroup()->getId(), $post->getId()]));
    }

    public function testUpdateAnswer()
    {
        $repository = $this->loadFixtures([
            LoadPostVoteData::class,
            LoadPostSubscriberData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        /** @var EntityManager $em */
        $em = $client->getContainer()->get('doctrine')->getManager();
        $conn = $em->getConnection();
        /** @var Post $post */
        $post = $repository->getReference('post_1');
        $user = $repository->getReference('user_3');
        $answer = $conn->fetchAssoc('SELECT id, `option` FROM post_votes WHERE post_id = ? AND user_id = ?', [$post->getId(), $user->getId()]);
        $client->request('POST',
            self::API_ENDPOINT.'/'.$post->getId().'/vote', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user3"'],
            json_encode(['option' => 'downvote'])
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(Post\Vote::OPTION_DOWNVOTE, $data['option']);
        $this->assertEquals($answer['id'], $data['id']);
        $this->assertNotEquals($answer['option'], $data['option']);
        // check social activity
        $tester = new SocialActivityTester($em);
        $tester->assertActivitiesCount(1);
        $tester->assertActivity(SocialActivity::TYPE_OWN_POST_VOTED, $post->getUser()->getId());
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(1, $queue->count());
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendSocialActivity'));
    }

    public function testUpdateAnswerWithErrors()
    {
        $repository = $this->loadFixtures([
            LoadPostVoteData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        /** @var Post $post */
        $post = $repository->getReference('post_1');
        $client->request('POST',
            self::API_ENDPOINT.'/'.$post->getId().'/vote', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"'],
            json_encode(['option' => 'upvote'])
        );
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertContains(
            'User is already answered this petition',
            $data['errors']['children']['option']['errors']
        );
    }

    public function testUnsignPost()
    {
        $repository = $this->loadFixtures([
            LoadPostVoteData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        /** @var Post $post */
        $post = $repository->getReference('post_1');
        $user = $repository->getReference('user_2');
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$post->getId().'/vote', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')
            ->getConnection();
        // check social activity
        $count = (int)$conn->fetchColumn('SELECT COUNT(*) FROM post_votes WHERE post_id = ? AND user_id = ?', [$post->getId(), $user->getId()]);
        $this->assertSame(0, $count);
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|PostManager
     */
    private function getPostManagerMock($methods = [])
    {
        $container = $this->client->getContainer();
        return $this->getMockBuilder(PostManager::class)
            ->setMethods($methods)
            ->setConstructorArgs([
                $container->get('doctrine')->getManager(),
                $container->get('event_dispatcher')
            ])
            ->getMock();
    }
}