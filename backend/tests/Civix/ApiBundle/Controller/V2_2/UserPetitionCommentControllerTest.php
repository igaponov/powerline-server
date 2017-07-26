<?php

namespace Tests\Civix\ApiBundle\Controller\V2_2;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\UserPetition\Comment;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Tests\Civix\CoreBundle\DataFixtures\ORM\LoadUserPetitionCommentData;
use Tests\Civix\CoreBundle\DataFixtures\ORM\LoadUserPetitionCommentRateData;

class UserPetitionCommentControllerTest extends CommentControllerTestCase
{
    protected function getEndpoint()
    {
        return '/api/v2.2/user-petition-comments/{id}';
    }

    /**
     * @QueryCount(3)
     */
    public function testGetChildComments(): ReferenceRepository
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
            LoadUserPetitionCommentRateData::class,
        ])->getReferenceRepository();
        /** @var Comment $comment */
        $comment = $repository->getReference('petition_comment_1');
        /** @var User $user */
        $user = $repository->getReference('user_1');
        /** @var BaseComment[] $comments */
        $comments = [
            $repository->getReference('petition_comment_4'),
            $repository->getReference('petition_comment_5'),
            $repository->getReference('petition_comment_6'),
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
        $parent = $repository->getReference('petition_comment_1');
        /** @var User $user */
        $user = $repository->getReference('user_1');
        /** @var BaseComment $comment */
        $comment = $repository->getReference('petition_comment_5');
        /** @var BaseComment $cursor */
        $cursor = $repository->getReference('petition_comment_6');
        $this->getChildCommentsWithCursor($parent, $user, $comment, $cursor);
    }
}
