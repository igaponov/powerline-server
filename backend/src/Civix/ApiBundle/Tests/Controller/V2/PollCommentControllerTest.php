<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadPollCommentRateData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;

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
}