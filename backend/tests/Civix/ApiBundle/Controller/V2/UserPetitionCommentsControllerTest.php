<?php
namespace Tests\Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Tests\Controller\V2\CommentsControllerTest;
use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\CommentedInterface;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadActivityRelationsData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
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
            LoadActivityRelationsData::class,
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
            LoadActivityRelationsData::class,
        ])->getReferenceRepository();
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('user_petition_1');
        $this->createRootComment($entity);
    }
}