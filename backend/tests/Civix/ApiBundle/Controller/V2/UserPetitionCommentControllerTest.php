<?php
namespace Tests\Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Tests\Controller\V2\CommentControllerTestCase;
use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPetitionCommentKarmaData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionCommentRateData;

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
        /** @var BaseComment $comment */
        $comment = $repository->getReference('petition_comment_3');
        $this->updateComment($comment);
    }

    public function testUpdateCommentWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
        $comment = $repository->getReference('petition_comment_3');
        $this->updateCommentWithWrongCredentials($comment);
    }

    public function testDeleteComment()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
        $comment = $repository->getReference('petition_comment_3');
        $this->deleteComment($comment);
    }

    public function testDeleteCommentWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
        $comment = $repository->getReference('petition_comment_3');
        $this->deleteCommentWithWrongCredentials($comment);
    }

    public function testRateCommentWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentRateData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
        $comment = $repository->getReference('petition_comment_1');
        $this->rateCommentWithWrongCredentials($comment);
    }

    /**
     * @param $rate
     * @param $user
     * @dataProvider getRates
     */
    public function testRateCommentIsOk($rate, $user)
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentRateData::class,
            LoadGroupManagerData::class,
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
        $comment = $repository->getReference('petition_comment_1');
        $this->rateComment($comment, $rate, $user);
    }

    /**
     * @param $rate
     * @dataProvider getRates
     */
    public function testUpdateCommentRateIsOk($rate)
    {
        $repository = $this->loadFixtures([
            LoadGroupManagerData::class,
            LoadUserGroupData::class,
            LoadPetitionCommentKarmaData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
        $comment = $repository->getReference('petition_comment_3');
        $this->updateCommentRate($comment, $rate);
    }
}