<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionCommentData;

class UserPetitionCommentControllerTest extends CommentControllerTestCase
{
    protected function getApiEndpoint()
    {
        return '/api/v2/user-petition-comments/{id}';
    }

    public function testUpdateCommentIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('petition_comment_3');
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
            LoadUserPetitionCommentData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('petition_comment_3');
        $this->updateCommentWithWrongData($comment, $params, $errors);
    }

    public function testUpdateCommentWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('petition_comment_3');
        $this->updateCommentWithWrongCredentials($comment);
    }

    public function testDeleteComment()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('petition_comment_3');
        $this->deleteComment($comment);
    }

    public function testDeleteCommentWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('petition_comment_3');
        $this->deleteCommentWithWrongCredentials($comment);
    }
}