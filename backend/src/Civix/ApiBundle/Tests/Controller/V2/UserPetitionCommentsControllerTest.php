<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\CommentedInterface;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupOwnerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionSubscriberData;

class UserPetitionCommentsControllerTest extends CommentsControllerTest
{
    protected function getApiEndpoint()
    {
        return '/api/v2/user-petitions/{id}/comments';
    }

    public function testGetComments()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
        ])->getReferenceRepository();
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('user_petition_5');
        $comments = [
            $repository->getReference('petition_comment_2'),
            $repository->getReference('petition_comment_3'),
        ];
        $this->getComments($entity, $comments);
    }

    public function testGetFilteredComments()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
        ])->getReferenceRepository();
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('user_petition_5');
        $comments = [
            $repository->getReference('petition_comment_2'),
            $repository->getReference('petition_comment_3'),
        ];
        $this->getComments($entity, $comments, ['sort' => 'createdAt', 'sort_dir' => 'DESC']);
    }

    public function testGetChildComments()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $entity */
        $entity = $repository->getReference('petition_comment_2');
        $this->getChildComments($entity, 1);
    }

    public function testGetCommentsWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
        ])->getReferenceRepository();
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('user_petition_1');
        $this->getCommentsWithInvalidCredentials($entity);
    }

    public function testCreateComment()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
            LoadGroupManagerData::class,
            LoadUserPetitionSubscriberData::class,
        ])->getReferenceRepository();
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('user_petition_1');
        /** @var BaseComment $comment */
        $comment = $repository->getReference('petition_comment_3');
        $this->createComment($entity, $comment);
    }

    public function testCreateRootComment()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
            LoadGroupManagerData::class,
            LoadUserPetitionSubscriberData::class,
        ])->getReferenceRepository();
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('user_petition_1');
        /** @var User $user */
        $user = $repository->getReference('user_2');
        $this->createRootComment($entity, $user);
    }

    public function testCreateCommentMentionedContentOwner()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
            LoadGroupManagerData::class,
            LoadUserPetitionSubscriberData::class,
            LoadUserGroupOwnerData::class,
        ])->getReferenceRepository();
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('user_petition_1');
        /** @var BaseComment $comment */
        $comment = $repository->getReference('petition_comment_3');
        $this->createCommentMentionedContentOwner($entity, $comment);
    }

    public function testCreateCommentNotifyEveryone()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadUserGroupOwnerData::class,
            LoadUserPetitionCommentData::class,
            LoadUserPetitionSubscriberData::class,
        ])->getReferenceRepository();
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('user_petition_1');
        /** @var BaseComment $comment */
        $comment = $repository->getReference('petition_comment_3');
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
            LoadUserPetitionCommentData::class,
            LoadUserPetitionSubscriberData::class,
        ])->getReferenceRepository();
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('user_petition_1');
        /** @var BaseComment $comment */
        $comment = $repository->getReference('petition_comment_3');
        $this->createCommentWithEveryoneByMemberNotifyNobody($entity, $comment);
    }
}