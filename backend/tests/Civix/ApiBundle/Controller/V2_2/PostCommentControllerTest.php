<?php

namespace Tests\Civix\ApiBundle\Controller\V2_2;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Post\Comment;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Tests\Civix\CoreBundle\DataFixtures\ORM\LoadPostCommentData;
use Tests\Civix\CoreBundle\DataFixtures\ORM\LoadPostCommentRateData;

class PostCommentControllerTest extends CommentControllerTestCase
{
    protected function getEndpoint()
    {
        return '/api/v2.2/post-comments/{id}';
    }

    /**
     * @QueryCount(3)
     */
    public function testGetChildComments(): ReferenceRepository
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
            LoadPostCommentRateData::class,
        ])->getReferenceRepository();
        /** @var Comment $comment */
        $comment = $repository->getReference('post_comment_1');
        /** @var User $user */
        $user = $repository->getReference('user_1');
        /** @var BaseComment[] $comments */
        $comments = [
            $repository->getReference('post_comment_4'),
            $repository->getReference('post_comment_5'),
            $repository->getReference('post_comment_6'),
        ];
        $this->getChildComments($comment, $user, $comments);

        return $repository;
    }

    /**
     * @param ReferenceRepository $repository
     * @depends testGetChildComments
     * @QueryCount(3)
     */
    public function testGetChildCommentsWithCursor(ReferenceRepository $repository): void
    {
        /** @var Comment $parent */
        $parent = $repository->getReference('post_comment_1');
        /** @var User $user */
        $user = $repository->getReference('user_1');
        /** @var BaseComment $comment */
        $comment = $repository->getReference('post_comment_5');
        /** @var BaseComment $cursor */
        $cursor = $repository->getReference('post_comment_6');
        $this->getChildCommentsWithCursor($parent, $user, $comment, $cursor);
    }
}
