<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostCommentData;

class PostCommentsControllerTest extends CommentsControllerTest
{
    protected function getApiEndpoint()
    {
        return '/api/v2/posts/{id}/comments';
    }

    public function testGetComments()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('post_1');
        $this->getComments($entity, 1);
    }

    public function testGetCommentsWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('post_1');
        $this->getCommentsWithInvalidCredentials($entity);
    }

    public function testCreateComment()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('post_1');
        $comment = $repository->getReference('post_comment_3');
        $this->createComment($entity, $comment);
    }
}