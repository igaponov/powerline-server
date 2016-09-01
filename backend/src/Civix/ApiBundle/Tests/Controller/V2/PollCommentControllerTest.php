<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionCommentData;

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

}