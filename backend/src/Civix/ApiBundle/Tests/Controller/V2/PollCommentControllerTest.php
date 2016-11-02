<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadPollCommentRateData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Issue\PM354;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Doctrine\DBAL\Connection;

class PollCommentControllerTest extends CommentControllerTestCase
{
    protected function getApiEndpoint()
    {
        return '/api/v2/poll-comments/{id}';
    }

    public function testUpdateCommentIsOk()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('question_comment_1');
        $this->updateComment($comment);
    }

    /**
     * @param $params
     * @param $errors
     * @dataProvider getInvalidParams
     */
    public function testUpdateCommentWithWrongData($params, $errors)
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('question_comment_1');
        $this->updateCommentWithWrongData($comment, $params, $errors);
    }

    public function testUpdateCommentWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('question_comment_1');
        $this->updateCommentWithWrongCredentials($comment);
    }

    public function testDeleteComment()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('question_comment_1');
        $this->deleteComment($comment);
    }

    public function testDeleteCommentWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('question_comment_1');
        $this->deleteCommentWithWrongCredentials($comment);
    }

    public function testRateCommentWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadPollCommentRateData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('question_comment_1');
        $this->rateCommentWithWrongCredentials($comment);
    }

    /**
     * @param $params
     * @param $errors
     * @dataProvider getInvalidRates
     */
    public function testRateCommentWithWrongDataReturnsErrors($params, $errors)
    {
        $repository = $this->loadFixtures([
            LoadPollCommentRateData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('question_comment_1');
        $this->rateCommentWithWrongData($comment, $params, $errors);
    }

    /**
     * @param $rate
     * @param $user
     * @dataProvider getRates
     */
    public function testRateCommentIsOk($rate, $user)
    {
        $repository = $this->loadFixtures([
            LoadPollCommentRateData::class,
            LoadGroupManagerData::class,
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('question_comment_1');
        $this->rateComment($comment, $rate, $user);
    }

    /**
     * @param $rate
     * @dataProvider getRates
     */
    public function testUpdateCommentRateIsOk($rate)
    {
        $repository = $this->loadFixtures([
            LoadPollCommentRateData::class,
            LoadGroupManagerData::class,
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('question_comment_3');
        $this->updateCommentRate($comment, $rate);
    }

    public function testRateNewsCommentMarkActivityAsRead()
    {
        $repository = $this->loadFixtures([
            PM354::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('user_1');
        $activity = $repository->getReference('activity_pm354');
        $comment = $repository->getReference('group_news_1_comment_1');
        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $uri = str_replace('{id}', $comment->getId(), $this->getApiEndpoint().'/rate');
        $params = ['rate_value' => 'up'];
        $client->request('POST', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(1, $data['rate_sum']);
        $this->assertEquals(1, $data['rates_count']);
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.dbal.default_connection');
        $rate = $conn->fetchColumn('
            SELECT a.rate_up FROM activities a 
            INNER JOIN activities_read ar ON a.id = ar.activity_id
            WHERE a.id = ? AND ar.user_id = ?',
            [$activity->getId(), $user->getId()]
        );
        $this->assertEquals(1, $rate);
    }
}