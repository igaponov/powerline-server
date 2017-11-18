<?php

namespace Tests\Civix\ApiBundle\Controller\V2_2;

use Civix\CoreBundle\Entity\Activity;
use Symfony\Bridge\Doctrine\DataCollector\DoctrineDataCollector;
use Tests\Civix\ApiBundle\Controller\ActivityControllerTestCase;

class ActivityControllerTest extends ActivityControllerTestCase
{
    private const API_ENDPOINT = '/api/v2.2/activities';

    /**
     * @todo cache user's newsfeed
     * @param array $params
     * @param array $references
     * @param int $queryCount
     * @dataProvider getSets
     */
    public function testGetActivitiesIsOk(array $params, array $references, int $queryCount): void
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        $client = $this->client;
        $client->enableProfiler();
        $client->request('GET', self::API_ENDPOINT, $params, [], ['HTTP_Authorization'=>'Bearer user1']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        /** @var array $data */
        $data = json_decode($response->getContent(), true);
        foreach ($data as $key => $item) {
            if ($item['owner']['type'] === 'user') {
                $this->assertNotContains('src', $item['owner']['avatar_file_path']);
            } elseif ($item['owner']['type'] === 'group') {
                $this->assertContains('src', $item['owner']['avatar_file_path']);
            } elseif ($item['owner']['type'] === 'representative') {
                $this->assertContains('representative', $item['owner']['avatar_file_path']);
            } else {
                $this->fail('Unexpected owner data type '.$item['owner']['type']);
            }
            /** @var Activity $activity */
            $activity = $repository->getReference($references[$key]);
            if ($item['group']['official_name'] === 'group1') {
                $this->assertSame($item['group']['user_role'], 'owner');
            } else {
                $this->assertNull($item['group']['user_role']);
            }
            if (in_array($item['user']['username'], ['user2', 'user4'], true)) {
                $this->assertSame($item['user']['follow_status'], 'active');
            } elseif ($item['user']['username'] === 'user3') {
                $this->assertSame($item['user']['follow_status'], 'pending');
            } else {
                $this->assertNull($item['user']['follow_status']);
            }
            $this->assertActivity($item, $activity);
        }
        /** @var DoctrineDataCollector $dataCollector */
        $dataCollector = $client->getProfile()->getCollector('db');
        $this->assertSame($queryCount, $dataCollector->getQueryCount());
    }

    public function getSets(): array
    {
        return [
            'default' => [
                [],
                [
                    'activity_leader_news',
                    'activity_payment_request',
                    'activity_petition',
                    'activity_user_petition',
                    'activity_post',
                    'activity_crowdfunding_payment_request',
                    'activity_question',
                    'activity_leader_event',
                ],
                12
            ],
            'followed' => [
                ['followed' => true],
                [
                    'activity_payment_request',
                    'activity_petition',
                    'activity_petition3',
                    'activity_question',
                    'activity_leader_event',
                ],
                12
            ],
            'non-followed' => [
                ['non_followed' => true],
                [
                    'activity_leader_news',
                    'activity_user_petition',
                    'activity_post',
                    'activity_crowdfunding_payment_request',
                ],
                13
            ]
        ];
    }
}
