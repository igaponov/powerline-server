<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionCommentData;
use Doctrine\DBAL\Connection;

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
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('user_petition_1');
        $comment = $repository->getReference('petition_comment_3');
        $this->createComment($entity, $comment);
        /** @var Connection $conn */
        $conn = $this->client->getContainer()->get('doctrine.dbal.default_connection');
        $count = $conn->fetchColumn(
            'SELECT COUNT(*) FROM social_activities sa WHERE type = ? and recipient_id = ?',
            [SocialActivity::TYPE_OWN_USER_PETITION_COMMENTED, $entity->getUser()->getId()]
        );
        $this->assertEquals(1, $count);
    }
}