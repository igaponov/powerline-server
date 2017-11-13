<?php

namespace Tests\Civix\ApiBundle\Controller\V2_2;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Tests\Civix\CoreBundle\DataFixtures\ORM\LoadPostCommentData;
use Tests\Civix\CoreBundle\DataFixtures\ORM\LoadPostCommentRateData;

class PostCommentsControllerTest extends CommentsControllerTestCase
{
    protected function getEndpoint()
    {
        return '/api/v2.2/posts/{id}/comments';
    }

    /**
     * @QueryCount(5)
     */
    public function testGetComments(): ReferenceRepository
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
            LoadPostCommentRateData::class,
        ])->getReferenceRepository();
        /** @var Post $post */
        $post = $repository->getReference('post_1');
        /** @var User $user */
        $user = $repository->getReference('user_1');
        /** @var BaseComment[] $comments */
        $comments = [
            $repository->getReference('post_comment_1'),
            $repository->getReference('post_comment_2'),
        ];
        $this->getComments($post, $user, $comments);

        return $repository;
    }

    /**
     * @param ReferenceRepository $repository
     * @depends testGetComments
     * @QueryCount(5)
     */
    public function testGetCommentsWithCursor(ReferenceRepository $repository)
    {
        /** @var Post $post */
        $post = $repository->getReference('post_1');
        /** @var User $user */
        $user = $repository->getReference('user_1');
        /** @var BaseComment $comment */
        $comment = $repository->getReference('post_comment_2');
        $this->getCommentsWithCursor($post, $user, $comment);
    }
}
