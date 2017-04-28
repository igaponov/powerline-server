<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\CommentedInterface;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostSubscriberData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupOwnerData;

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

    public function testGetChildComments()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $entity */
        $entity = $repository->getReference('post_comment_2');
        $this->getChildComments($entity, 1);
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

    public function testCreateRootComment()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
            LoadGroupManagerData::class,
            LoadPostSubscriberData::class,
        ])->getReferenceRepository();
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('post_1');
        /** @var User $user */
        $user = $repository->getReference('user_2');
        $this->createRootComment($entity, $user);
    }

    public function testCreateCommentMentionedContentOwner()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
            LoadGroupManagerData::class,
            LoadPostSubscriberData::class,
            LoadUserGroupOwnerData::class,
        ])->getReferenceRepository();
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('post_1');
        /** @var BaseComment $comment */
        $comment = $repository->getReference('post_comment_3');
        $this->createCommentMentionedContentOwner($entity, $comment);
    }

    public function testCreateCommentNotifyEveryone()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadUserGroupOwnerData::class,
            LoadPostCommentData::class,
            LoadPostSubscriberData::class,
        ])->getReferenceRepository();
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('post_1');
        /** @var BaseComment $comment */
        $comment = $repository->getReference('post_comment_3');
        $users = [
            $repository->getReference('user_1'),
            $repository->getReference('user_2'),
            $repository->getReference('user_4'),
        ];
        $this->createCommentNotifyEveryone($entity, $comment, $users);
    }

    public function testCreateCommentWithEveryoneByMemberNotifyNobody()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadUserGroupOwnerData::class,
            LoadPostCommentData::class,
            LoadPostSubscriberData::class,
        ])->getReferenceRepository();
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('post_1');
        /** @var BaseComment $comment */
        $comment = $repository->getReference('post_comment_3');
        $this->createCommentWithEveryoneByMemberNotifyNobody($entity, $comment);
    }
}