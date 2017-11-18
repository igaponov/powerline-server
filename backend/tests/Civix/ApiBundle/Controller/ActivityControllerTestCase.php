<?php

namespace Tests\Civix\ApiBundle\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\Poll\EducationalContext;
use Civix\CoreBundle\Entity\Post\Vote;
use Civix\CoreBundle\Tests\DataFixtures\ORM;
use Symfony\Bundle\FrameworkBundle\Client;
use Tests\Civix\CoreBundle\DataFixtures\ORM\LoadBlockedUserData;

class ActivityControllerTestCase extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

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

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::bootFixtureLoader();
        self::$fixtureLoader->loadFixtures([
            ORM\LoadUserFollowerData::class,
            ORM\LoadActivityRelationsData::class,
            ORM\LoadUserPetitionSubscriberData::class,
            ORM\LoadPostSubscriberData::class,
            ORM\LoadPollSubscriberData::class,
            ORM\LoadEducationalContextData::class,
            ORM\LoadActivityReadAuthorData::class,
            ORM\LoadUserGroupOwnerData::class,
            ORM\LoadPostVoteData::class,
            ORM\LoadUserPetitionSignatureData::class,
            ORM\LoadPostCommentData::class,
            ORM\Group\LoadQuestionCommentData::class,
            ORM\LoadUserPetitionCommentData::class,
            ORM\Issue\LoadLocalGroupActivityData::class,
            ORM\LoadUserGroupData::class,
            ORM\LoadUserGroupOwnerData::class,
            ORM\LoadGroupManagerData::class,
            LoadBlockedUserData::class,
        ]);
    }

    /**
     * @param $item
     * @param Activity $activity
     *
     * Group by selects first row from joined table in MySQL and last one in SQLite.
     * We check getComments()->last() here but in prod (MySQL) it'll be first one.
     */
    protected function assertActivity($item, Activity $activity): void
    {
        $this->assertEquals($item['id'], $activity->getId());
        $this->assertNotEmpty($item['user']);
        $this->assertArrayHasKey('group', $item);
        if ($item['entity']['type'] === 'user-petition') {
            $userPetition = $item['user_petition'];
            $this->assertTrue($userPetition['is_subscribed']);
            $this->assertCount(1, $userPetition['signatures']);
            $this->assertArrayHasKey('comments', $userPetition);
            $petition = $activity->getPetition();
            if ($petition->getComments()->count()) {
                $this->assertCount(1, $userPetition['comments']);
                $this->assertSame(
                    $petition->getComments()->last()->getId(),
                    $userPetition['comments'][0]['id']
                );
            }
            $this->assertContains(
                $petition->getFacebookThumbnail()->getName(),
                $userPetition['facebook_thumbnail']
            );
            $this->assertContains(
                $petition->getImage()->getName(),
                $userPetition['image']
            );
        } elseif ($item['entity']['type'] === 'post') {
            $postData = $item['post'];
            $this->assertTrue($postData['is_subscribed']);
            $this->assertArrayHasKey('upvotes_count', $item);
            $this->assertArrayHasKey('downvotes_count', $item);
            $this->assertCount(1, $postData['votes']);
            $this->assertSame(Vote::OPTION_UPVOTE, $postData['votes'][0]['option']);
            $this->assertArrayHasKey('comments', $postData);
            $post = $activity->getPost();
            if ($post->getComments()->count()) {
                $this->assertCount(1, $postData['comments']);
                $this->assertSame(
                    $post->getComments()->last()->getId(),
                    $postData['comments'][0]['id']
                );
            }
            $this->assertContains(
                $post->getFacebookThumbnail()->getName(),
                $postData['facebook_thumbnail']
            );
            $this->assertContains(
                $post->getImage()->getName(),
                $postData['image']
            );
        } elseif ($item['group']['group_type_label'] !== 'country' && in_array($item['entity']['type'], ['question', 'petition'], true)) {
            $this->assertNotEmpty($item['group']['avatar_file_path']);
            $pollData = $item['poll'];
            $this->assertCount(0, $pollData['answers']);
            $this->assertArrayHasKey('educational_context', $pollData);
            if ($item['entity']['type'] === 'question') {
                $this->assertTrue($pollData['is_subscribed']);
                /** @var array $educationalContexts */
                $educationalContexts = $pollData['educational_context'];
                $this->assertCount(2, $educationalContexts);
                foreach ($educationalContexts as $educationalContext) {
                    if ($educationalContext['type'] !== EducationalContext::TEXT_TYPE) {
                        $this->assertNotEmpty($educationalContext['preview']);
                    }
                }
            } else {
                $this->assertFalse($item['poll']['is_subscribed']);
            }
            $poll = $activity->getQuestion();
            if ($poll->getComments()->count()) {
                $this->assertCount(1, $pollData['comments']);
                $this->assertSame(
                    $poll->getComments()->last()->getId(),
                    $pollData['comments'][0]['id']
                );
            }
            $this->assertCount($poll->getOptions()->count(), $pollData['options']);
            foreach ($poll->getOptions() as $key => $option) {
                $this->assertSame($option->getId(), $pollData['options'][$key]['id']);
                $this->assertSame($option->getValue(), $pollData['options'][$key]['value']);
                $this->assertSame($option->getPaymentAmount(), $pollData['options'][$key]['payment_amount']);
                $this->assertSame($option->getIsUserAmount(), $pollData['options'][$key]['is_user_amount']);
            }
        }
        if ($item['entity']['type'] === 'micro-petition') {
            $this->assertArrayHasKey('comments_count', $item);
            $this->assertArrayHasKey('answers', $item);
            $this->assertInternalType('array', $item['answers']);
        }
        if ($item['expire_at'] && strtotime($item['expire_at']) < time()) {
            $this->assertSame('expired', $item['zone']);
        } elseif (in_array($item['entity']['type'], ['user-petition', 'post'], true)) {
            $this->assertSame('non_prioritized', $item['zone']);
        } else {
            $this->assertSame('prioritized', $item['zone']);
        }
        $this->assertArrayHasKey('description_html', $item);
    }
}
