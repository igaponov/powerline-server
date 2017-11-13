<?php
namespace Tests\Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\Karma;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\Report\PostResponseReport;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Service\PostManager;
use Civix\CoreBundle\Test\SocialActivityTester;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadRepresentativeData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostSubscriberData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostVoteData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostVoteKarmaData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSpamPostData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Report\LoadPostResponseReportData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Report\LoadUserReportData;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Client;

class PostControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/posts';

    /**
     * @var null|Client
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    protected function tearDown(): void
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
        /** @var Post $post */
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
        /** @var array $payload */
        $payload = $data['payload'];
        $this->assertCount(6, $payload);
        foreach ($payload as $item) {
            if ($post->getId() === $item['id']) {
                $this->assertCount(1, $item['votes']);
                $this->assertEquals($answer->getOption(), $item['votes'][0]['option']);
                $this->assertArrayHasKey('html_body', $item);
                $this->assertContains($post->getImage()->getName(), $item['image']);
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
        $this->assertSame($post->isAutomaticBoost(), $data['automatic_boost']);
        $this->assertContains($post->getImage()->getName(), $data['image']);
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
        $image = $post->getImage()->getName();
        $client = $this->client;
        $hashTags = [
            '#testHashTag',
            '#powerlineHashTag',
        ];
        $params = [
            'body' => $faker->text."\n".implode(' ', $hashTags),
            'automatic_boost' => false,
            'image' => base64_encode(file_get_contents(__DIR__.'/../../../../data/image.png')),
        ];
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization' => 'Bearer user1'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($params['body'], $data['body']);
        $this->assertSame($params['automatic_boost'], $data['automatic_boost']);
        $this->assertNotSame($image, $data['image']);
        $storage = $client->getContainer()->get('civix_core.storage.array');
        $this->assertCount(1, $storage->getFiles('image_post_fs'));
    }

    public function testBoostPostWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadPostData::class,
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        /** @var Post $post */
        $post = $repository->getReference('post_2');
        $this->assertFalse($post->isBoosted());
        $client = $this->client;
        $client->request('PATCH',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user4"']
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param $fixtures
     * @param $user
     * @param $reference
     * @dataProvider getValidPostCredentialsForBoostRequest
     */
    public function testBoostPost($fixtures, $user, $reference)
    {
        $repository = $this->loadFixtures($fixtures)->getReferenceRepository();
        /** @var Post $post */
        $post = $repository->getReference($reference);
        $this->assertFalse($post->isBoosted());
        $client = $this->client;
        $client->request('PATCH',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']
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
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        /** @var Post $post */
        $post = $repository->getReference('post_4');
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

    public function testDeletePostByGroupManager()
    {
        $repository = $this->loadFixtures([
            LoadSpamPostData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        /** @var Post $post */
        $post = $repository->getReference('post_4');
        $client = $this->client;
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
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

    public function testVoteOnPost()
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
        /** @var User $user */
        $user = $repository->getReference('user_2');
        /** @var Post $post */
        $post = $repository->getReference('post_2');
        $option = 'upvote';
        $client->request('POST',
            self::API_ENDPOINT.'/'.$post->getId().'/vote', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"'],
            json_encode(['option' => $option])
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
        $report = $em
            ->getRepository(PostResponseReport::class)
            ->getPostResponseReport($user, $post);
        $this->assertSame($option, $report->getVote());
        $result = $client->getContainer()->get('doctrine.dbal.default_connection')
            ->fetchAssoc('SELECT * FROM karma');
        $this->assertArraySubset([
            'user_id' => $post->getUser()->getId(),
            'type' => Karma::TYPE_RECEIVE_UPVOTE_ON_POST,
            'points' => 2,
            'metadata' => serialize([
                'post_id' => $post->getId(),
                'vote_id' => $data['id'],
            ]),
        ], $result);
    }

    public function testVoteOnPostWithoutAutomaticBoost()
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
        $post = $repository->getReference('post_4');
        $client->request('POST',
            self::API_ENDPOINT.'/'.$post->getId().'/vote', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"'],
            json_encode(['option' => 'downvote'])
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(Post\Vote::OPTION_DOWNVOTE, $data['option']);
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(0, $queue->count());
        $count = $client->getContainer()->get('doctrine.dbal.default_connection')
            ->fetchColumn('SELECT COUNT(*) FROM karma');
        $this->assertEquals(0, $count);
    }

    /**
     * @param $postReference
     * @param $userReference
     * @param $option
     * @dataProvider getOptions
     */
    public function testUpdateVote($postReference, $userReference, $option)
    {
        $repository = $this->loadFixtures([
            LoadPostVoteData::class,
            LoadPostSubscriberData::class,
            LoadPostResponseReportData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        /** @var EntityManager $em */
        $em = $client->getContainer()->get('doctrine')->getManager();
        $conn = $em->getConnection();
        /** @var Post $post */
        $post = $repository->getReference($postReference);
        /** @var User $user */
        $user = $repository->getReference($userReference);
        $answer = $conn->fetchAssoc('SELECT id, `option` FROM post_votes WHERE post_id = ? AND user_id = ?', [$post->getId(), $user->getId()]);
        $client->request('POST',
            self::API_ENDPOINT.'/'.$post->getId().'/vote', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="'.$user->getUsername().'"'],
            json_encode(['option' => $option])
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(
            array_search($option, Post\Vote::getOptionTitles(), true),
            $data['option']
        );
        $this->assertEquals($answer['id'], $data['id']);
        $this->assertNotEquals($answer['option'], $data['option']);
        // check social activity
        if ($postReference === 'post_1') {
            $tester = new SocialActivityTester($em);
            $tester->assertActivitiesCount(1);
            $tester->assertActivity(SocialActivity::TYPE_OWN_POST_VOTED,
                $post->getUser()->getId()
            );
            $queue = $client->getContainer()->get('civix_core.mock_queue_task');
            $this->assertEquals(1, $queue->count());
            $this->assertEquals(1, $queue->hasMessageWithMethod('sendSocialActivity'));
        }
        $report = $em->getRepository(PostResponseReport::class)
            ->getPostResponseReport($user, $post);
        $this->assertSame($option, $report->getVote());
        $result = $conn->fetchAssoc('SELECT * FROM karma');
        if ($option === 'upvote') {
            $this->assertArraySubset([
                'user_id' => $post->getUser()->getId(),
                'type' => Karma::TYPE_RECEIVE_UPVOTE_ON_POST,
                'points' => 2,
                'metadata' => serialize([
                    'post_id' => $post->getId(),
                    'vote_id' => $data['id'],
                ]),
            ], $result);
        } else {
            $this->assertFalse((bool)$result);
        }
    }

    public function getOptions()
    {
        return [
            ['post_3', 'user_4', 'downvote'], // change upvote to downvote
            ['post_1', 'user_2', 'upvote'], // change downvote to upvote
            ['post_1', 'user_3', 'downvote'], // change ignore to downvote
            ['post_1', 'user_3', 'upvote'], // change ignore to upvote
        ];
    }

    public function testUnvotePost()
    {
        $repository = $this->loadFixtures([
            LoadPostResponseReportData::class,
            LoadPostVoteKarmaData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        /** @var Post $post */
        $post = $repository->getReference('post_1');
        /** @var User $user */
        $user = $repository->getReference('user_2');
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$post->getId().'/vote', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var EntityManager $em */
        $em = $client->getContainer()
            ->get('doctrine')->getManager();
        $conn = $em->getConnection();
        // check social activity
        $count = (int)$conn->fetchColumn('SELECT COUNT(*) FROM post_votes WHERE post_id = ? AND user_id = ?', [$post->getId(), $user->getId()]);
        $this->assertSame(0, $count);
        $result = $em->getRepository(PostResponseReport::class)
            ->getPostResponseReport($user, $post);
        $this->assertNull($result);
        $results = $conn->fetchAll('SELECT * FROM karma');
        $this->assertCount(0, $results);
    }

    public function testMarkPostAsSpam()
    {
        $repository = $this->loadFixtures([
            LoadPostData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        /** @var Post $post */
        $post = $repository->getReference('post_2');
        $user = $repository->getReference('user_2');
        $client->request('POST',
            self::API_ENDPOINT.'/'.$post->getId().'/spam', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $conn = $client->getContainer()->get('doctrine.dbal.default_connection');
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM spam_posts WHERE post_id = ? AND user_id = ?', [$post->getId(), $user->getId()]);
        $this->assertEquals(1, $count);
    }

    public function testUnmarkPostAsSpam()
    {
        $repository = $this->loadFixtures([
            LoadSpamPostData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        /** @var Post $post */
        $post = $repository->getReference('post_1');
        $user = $repository->getReference('user_2');
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$post->getId().'/spam', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user3"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $conn = $client->getContainer()->get('doctrine.dbal.default_connection');
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM spam_posts WHERE post_id = ? AND user_id = ?', [$post->getId(), $user->getId()]);
        $this->assertEquals(0, $count);
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidPostCredentialsForGetResponsesRequest
     */
    public function testGetPostResponsesWithWrongCredentialsThrowsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadPostVoteData::class,
        ])->getReferenceRepository();
        $post = $repository->getReference($reference);
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$post->getId().'/responses', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testGetPostResponsesIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserReportData::class,
            LoadPostResponseReportData::class,
        ])->getReferenceRepository();
        /** @var Post $post */
        $post = $repository->getReference('post_1');
        /** @var Post\Vote[] $votes */
        $votes = [
            $repository->getReference('post_answer_1'),
            $repository->getReference('post_answer_2'),
            $repository->getReference('post_answer_3'),
        ];
        /** @var User[] $users */
        $users = [
            $repository->getReference('user_1'),
            $repository->getReference('user_2'),
            $repository->getReference('user_3'),
        ];
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$post->getId().'/responses', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(3, $data);
        foreach ($users as $k => $user) {
            $this->assertSame($votes[$k]->getOptionTitle(), $data[$k]['vote']);
            $this->assertEquals($user->getLatitude(), $data[$k]['latitude']);
            $this->assertEquals($user->getLongitude(), $data[$k]['longitude']);
            if (in_array($user->getUsername(), ['user1', 'user3'], true)) {
                $this->assertSame('US', $data[$k]['country']);
                $this->assertSame('NY', $data[$k]['state']);
                $this->assertSame('New York', $data[$k]['locality']);
                $this->assertSame(['United States', 'New York'], $data[$k]['districts']);
                $this->assertNotEmpty($data[$k]['representatives']);
            } else {
                $this->assertEmpty($data[$k]['country']);
                $this->assertEmpty($data[$k]['state']);
                $this->assertEmpty($data[$k]['locality']);
                $this->assertEmpty($data[$k]['districts']);
                $this->assertEmpty($data[$k]['representatives']);
            }
        }
    }

    public function testGetPostAnalyticsIsOk()
    {
        $repository = $this->loadFixtures([
            LoadPostVoteData::class,
            LoadRepresentativeData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        /** @var Post $post */
        $post = $repository->getReference('post_1');
        $vote1 = [
            'upvotes' => '1',
            'downvotes' => '0',
            'user' => '0',
            'author' => '1',
        ];
        $vote2 = [
            'upvotes' => '0',
            'downvotes' => '1',
            'user' => '1',
            'author' => '0',
        ];
        $results = [
            array_merge([
                'representative' => $repository->getReference('cicero_representative_bo'),

            ], $vote1),
            array_merge([
                'representative' => $repository->getReference('cicero_representative_jb'),

            ], $vote1),
            array_merge([
                'representative' => $repository->getReference('cicero_representative_rm'),

            ], $vote2),
            array_merge([
                'representative' => $repository->getReference('cicero_representative_kg'),

            ], $vote2),
            array_merge([
                'representative' => $repository->getReference('cicero_representative_eh'),

            ], $vote2),
        ];
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$post->getId().'/analytics', [], [], ['HTTP_Authorization'=>'Bearer user2']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(['upvotes' => '1', 'downvotes' => '1'], $data['total']);
        /** @var array $representatives */
        $representatives = $data['representatives'];
        $this->assertCount(5, $representatives);
        foreach ($representatives as $key => $item) {
            /** @var Representative $representative */
            $representative = $results[$key]['representative'];
            $this->assertEquals($representative->getCiceroId(), $item['cicero_id']);
            $this->assertSame($representative->getFirstName(), $item['first_name']);
            $this->assertSame($representative->getLastName(), $item['last_name']);
            $this->assertSame($representative->getOfficialTitle(), $item['official_title']);
            $this->assertSame($results[$key]['upvotes'], $item['upvotes']);
            $this->assertSame($results[$key]['downvotes'], $item['downvotes']);
            $this->assertSame($results[$key]['user'], $item['user']);
            $this->assertSame($results[$key]['author'], $item['author']);
        }
        $representatives = $data['most_popular'];
        $this->assertCount(5, $representatives);
        foreach ($representatives as $key => $item) {
            /** @var Representative $representative */
            $representative = $results[$key]['representative'];
            $this->assertEquals($representative->getCiceroId(), $item['cicero_id']);
            $this->assertSame($representative->getFirstName(), $item['first_name']);
            $this->assertSame($representative->getLastName(), $item['last_name']);
            $this->assertSame($representative->getOfficialTitle(), $item['official_title']);
            $this->assertSame($results[$key]['upvotes'], $item['upvotes']);
            $this->assertSame($results[$key]['downvotes'], $item['downvotes']);
        }
    }

    public function getValidPostCredentialsForBoostRequest()
    {
        return [
            'creator' => [[LoadPostData::class], 'user1', 'post_2'],
            'owner' => [[LoadPostData::class], 'user2', 'post_2'],
            'manager' => [[LoadPostData::class, LoadGroupManagerData::class], 'user3', 'post_2'],
        ];
    }

    public function getInvalidPostCredentialsForGetResponsesRequest()
    {
        return [
            'manager' => ['user2', 'post_1'],
            'member' => ['user4', 'post_1'],
            'outlier' => ['user1', 'post_4'],
        ];
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|PostManager
     */
    private function getPostManagerMock(array $methods = [])
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