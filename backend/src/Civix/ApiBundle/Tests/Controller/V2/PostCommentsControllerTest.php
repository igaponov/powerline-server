<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\CommentedInterface;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostSubscriberData;

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
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('post_1');
        $this->getComments($entity, 1);
    }

    public function testGetCommentsWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('post_1');
        $this->getCommentsWithInvalidCredentials($entity);
    }

    public function testCreateComment()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
            LoadGroupManagerData::class,
            LoadPostSubscriberData::class,
        ])->getReferenceRepository();
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('post_1');
        /** @var BaseComment $comment */
        $comment = $repository->getReference('post_comment_3');
        $this->createComment($entity, $comment);
    }
}