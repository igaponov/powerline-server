<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostCommentData;

class PostCommentControllerTest extends CommentControllerTestCase
{
    protected function getApiEndpoint()
    {
        return '/api/v2/post-comments/{id}';
    }

    public function testUpdateCommentIsOk()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('post_comment_3');
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
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('post_comment_3');
        $this->updateCommentWithWrongData($comment, $params, $errors);
    }

    public function testUpdateCommentWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('post_comment_3');
        $this->updateCommentWithWrongCredentials($comment);
    }

    public function testDeleteComment()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('post_comment_3');
        $this->deleteComment($comment);
    }

    public function testDeleteCommentWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('post_comment_3');
        $this->deleteCommentWithWrongCredentials($comment);
    }

}