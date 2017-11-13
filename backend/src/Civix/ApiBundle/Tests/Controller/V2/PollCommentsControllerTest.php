<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\CommentedInterface;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadPollCommentRateData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadActivityRelationsData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPollSubscriberData;

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
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('group_question_1');
        $comments = [
            $repository->getReference('question_comment_1'),
            $repository->getReference('question_comment_3'),
            $repository->getReference('question_comment_2'),
        ];
        $this->getComments($entity, $comments);
    }

    public function testGetFilteredComments()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
        ])->getReferenceRepository();
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('group_question_1');
        $comments = [
            $repository->getReference('question_comment_1'),
            $repository->getReference('question_comment_2'),
            $repository->getReference('question_comment_3'),
        ];
        $this->getComments($entity, $comments, ['sort' => 'createdAt', 'sort_dir' => 'DESC']);
    }

    public function testGetChildComments()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
        ])->getReferenceRepository();
        /** @var BaseComment $entity */
        $entity = $repository->getReference('question_comment_1');
        $this->getChildComments($entity, 1);
    }

    public function testGetCommentsWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
        ])->getReferenceRepository();
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('group_question_3');
        $this->getCommentsWithInvalidCredentials($entity);
    }

    public function testGetCommentsWithRate()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
            LoadGroupManagerData::class,
            LoadPollCommentRateData::class,
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
        /** @var array $payload */
        $payload = $data['payload'];
        $this->assertCount(3, $payload);
        $asserted = false;
        foreach ($payload as $item) {
            if ($item['id'] === $comment->getId()) {
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
            LoadActivityRelationsData::class,
            LoadQuestionCommentData::class,
            LoadGroupManagerData::class,
            LoadPollSubscriberData::class,
        ])->getReferenceRepository();
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('group_question_1');
        /** @var BaseComment $comment */
        $comment = $repository->getReference('question_comment_1');
        $this->createComment($entity, $comment);
    }

    public function testCreateRootComment()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
            LoadGroupManagerData::class,
            LoadPollSubscriberData::class,
            LoadActivityRelationsData::class,
        ])->getReferenceRepository();
        /** @var CommentedInterface $entity */
        $entity = $repository->getReference('group_question_1');
        $this->createRootComment($entity);
    }
}