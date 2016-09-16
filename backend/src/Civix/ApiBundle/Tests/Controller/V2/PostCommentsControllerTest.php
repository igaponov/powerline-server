<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostSubscriberData;
use Doctrine\DBAL\Connection;

class PostCommentsControllerTest extends CommentsControllerTest
{
    protected function getApiEndpoint()
    {
        return '/api/v2/posts/{id}/comments';
    }

    public function testGetComments()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('post_1');
        $this->getComments($entity, 1);
    }

    public function testGetCommentsWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('post_1');
        $this->getCommentsWithInvalidCredentials($entity);
    }

    public function testCreateComment()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
            LoadGroupManagerData::class,
            LoadPostSubscriberData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('post_1');
        $comment = $repository->getReference('post_comment_3');
        $this->createComment($entity, $comment);
        /** @var Connection $conn */
        $conn = $this->client->getContainer()->get('doctrine.dbal.default_connection');
        $count = $conn->fetchColumn(
            'SELECT COUNT(*) FROM social_activities sa WHERE type = ? and recipient_id = ?',
            [SocialActivity::TYPE_OWN_POST_COMMENTED, $entity->getUser()->getId()]
        );
        $this->assertEquals(1, $count);
    }
}