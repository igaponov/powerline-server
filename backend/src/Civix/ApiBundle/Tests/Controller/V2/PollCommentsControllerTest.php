<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadCommentRateData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowerData;

class PollCommentsControllerTest extends CommentsControllerTest
{
    protected function getApiEndpoint()
    {
        return '/api/v2/polls/{id}/comments';
    }

    public function testGetComments()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('group_question_1');
        $this->getComments($entity, 3);
    }

    public function testGetCommentsWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('group_question_3');
        $this->getCommentsWithInvalidCredentials($entity);
    }

    public function testGetCommentsWithRate()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
            LoadGroupManagerData::class,
            LoadCommentRateData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('group_question_1');
        $comment = $repository->getReference('question_comment_1');
        $client = $this->client;
        $uri = str_replace('{id}', $entity->getId(), $this->getApiEndpoint());
        $client->request('GET', $uri, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(3, $data['payload']);
        $asserted = false;
        foreach ($data['payload'] as $item) {
            if ($item['id'] == $comment->getId()) {
                $this->assertEquals('up', $item['rate_value']);
                $this->assertTrue($item['is_owner']);
                $asserted = true;
            }
        }
        $this->assertTrue($asserted);
    }

    public function testCreateComment()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
        ])->getReferenceRepository();
        $entity = $repository->getReference('group_question_1');
        $comment = $repository->getReference('question_comment_1');
        $this->createComment($entity, $comment);
    }
}