<?php

namespace Tests\Civix\ApiBundle\Controller\V2_2;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Tests\Civix\CoreBundle\DataFixtures\ORM\LoadUserPetitionCommentData;
use Tests\Civix\CoreBundle\DataFixtures\ORM\LoadUserPetitionCommentRateData;

class UserPetitionCommentsControllerTest extends CommentsControllerTestCase
{
    protected function getEndpoint(): string
    {
        return '/api/v2.2/user-petitions/{id}/comments';
    }

    /**
     * @QueryCount(5)
     */
    public function testGetComments(): ReferenceRepository
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
            LoadUserPetitionCommentRateData::class,
        ])->getReferenceRepository();
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_1');
        /** @var User $user */
        $user = $repository->getReference('user_1');
        /** @var BaseComment[] $comments */
        $comments = [
            $repository->getReference('petition_comment_1'),
            $repository->getReference('petition_comment_2'),
        ];
        $this->getComments($petition, $user, $comments);

        return $repository;
    }

    /**
     * @param ReferenceRepository $repository
     * @depends testGetComments
     * @QueryCount(5)
     */
    public function testGetCommentsWithCursor(ReferenceRepository $repository)
    {
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_1');
        /** @var User $user */
        $user = $repository->getReference('user_1');
        /** @var BaseComment $comment */
        $comment = $repository->getReference('petition_comment_2');
        $this->getCommentsWithCursor($petition, $user, $comment);
    }
}
