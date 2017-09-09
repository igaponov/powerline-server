<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostCommentRateData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostCommentKarmaData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;

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
        /** @var BaseComment $comment */
        $comment = $repository->getReference('post_comment_3');
        $this->updateComment($comment);
    }

    public function testUpdateCommentWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
        $comment = $repository->getReference('post_comment_3');
        $this->updateCommentWithWrongCredentials($comment);
    }

    public function testDeleteComment()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
        $comment = $repository->getReference('post_comment_3');
        $this->deleteComment($comment);
    }

    public function testDeleteCommentWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
        $comment = $repository->getReference('post_comment_3');
        $this->deleteCommentWithWrongCredentials($comment);
    }

    public function testRateCommentWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentRateData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
        $comment = $repository->getReference('post_comment_1');
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
            LoadPostCommentRateData::class,
            LoadGroupManagerData::class,
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
        $comment = $repository->getReference('post_comment_1');
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
            LoadPostCommentKarmaData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
        $comment = $repository->getReference('post_comment_3');
        $this->updateCommentRate($comment, $rate);
    }
}