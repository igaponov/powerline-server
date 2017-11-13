<?php

namespace Tests\Civix\ApiBundle\Controller\V2_2;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Tests\Civix\CoreBundle\DataFixtures\ORM\LoadPollCommentData;
use Tests\Civix\CoreBundle\DataFixtures\ORM\LoadPollCommentRateData;

class PollCommentsControllerTest extends CommentsControllerTestCase
{
    protected function getEndpoint()
    {
        return '/api/v2.2/polls/{id}/comments';
    }

    /**
     * @QueryCount(5)
     */
    public function testGetComments(): ReferenceRepository
    {
        $repository = $this->loadFixtures([
            LoadPollCommentData::class,
            LoadPollCommentRateData::class,
        ])->getReferenceRepository();
        /** @var Question $post */
        $post = $repository->getReference('group_question_1');
        /** @var User $user */
        $user = $repository->getReference('user_1');
        /** @var BaseComment[] $comments */
        $comments = [
            $repository->getReference('poll_comment_1'),
            $repository->getReference('poll_comment_2'),
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
        /** @var Question $post */
        $post = $repository->getReference('group_question_1');
        /** @var User $user */
        $user = $repository->getReference('user_1');
        /** @var BaseComment $comment */
        $comment = $repository->getReference('poll_comment_2');
        $this->getCommentsWithCursor($post, $user, $comment);
    }
}
