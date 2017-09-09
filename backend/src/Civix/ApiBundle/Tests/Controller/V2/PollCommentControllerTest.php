<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadPollCommentRateData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Issue\PM354;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPollCommentKarmaData;
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
        /** @var BaseComment $comment */
        $comment = $repository->getReference('question_comment_1');
        $this->updateComment($comment);
    }

    public function testUpdateCommentWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
        $comment = $repository->getReference('question_comment_1');
        $this->updateCommentWithWrongCredentials($comment);
    }

    public function testDeleteComment()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
        $comment = $repository->getReference('question_comment_1');
        $this->deleteComment($comment);
    }

    public function testDeleteCommentWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
        $comment = $repository->getReference('question_comment_1');
        $this->deleteCommentWithWrongCredentials($comment);
    }

    public function testRateCommentWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadPollCommentRateData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
        $comment = $repository->getReference('question_comment_1');
        $this->rateCommentWithWrongCredentials($comment);
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
        /** @var BaseComment $comment */
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
            LoadGroupManagerData::class,
            LoadPollCommentKarmaData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
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
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $rate = $conn->fetchColumn('
            SELECT a.rate_up FROM activities a 
            INNER JOIN activities_read ar ON a.id = ar.activity_id
            WHERE a.id = ? AND ar.user_id = ?',
            [$activity->getId(), $user->getId()]
        );
        $this->assertEquals(1, $rate);
    }
}