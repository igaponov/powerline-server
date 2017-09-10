<?php
namespace Tests\Civix\ApiBundle\Controller\V2;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostCommentKarmaData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostCommentRateData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;

class PostCommentControllerTest extends CommentControllerTestCase
{
    protected function getApiEndpoint(): string
    {
        return '/api/v2/post-comments/{id}';
    }

    public function testUpdateCommentIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
        $comment = $repository->getReference('post_comment_3');
        $this->updateComment($comment);
    }

    public function testUpdateCommentWithWrongCredentialsThrowsException(): void
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
        $comment = $repository->getReference('post_comment_3');
        $this->updateCommentWithWrongCredentials($comment);
    }

    public function testDeleteComment(): void
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
        $comment = $repository->getReference('post_comment_3');
        $this->deleteComment($comment);
    }

    public function testDeleteCommentWithWrongCredentialsThrowsException(): void
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $comment */
        $comment = $repository->getReference('post_comment_3');
        $this->deleteCommentWithWrongCredentials($comment);
    }

    public function testRateCommentWithWrongCredentialsThrowsException(): void
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
    public function testRateCommentIsOk($rate, $user): void
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
    public function testUpdateCommentRateIsOk($rate): void
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