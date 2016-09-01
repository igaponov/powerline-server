<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionCommentData;

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
        $entity = $repository->getReference('user_petition_5');
        $this->getComments($entity, 2);
    }

    public function testGetCommentsWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('user_petition_1');
        $this->getCommentsWithInvalidCredentials($entity);
    }

    public function testCreateComment()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('user_petition_1');
        $comment = $repository->getReference('petition_comment_3');
        $this->createComment($entity, $comment);
    }
}