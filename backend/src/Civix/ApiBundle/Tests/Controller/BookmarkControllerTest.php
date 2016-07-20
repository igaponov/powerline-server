<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Bookmark;
use Civix\CoreBundle\Entity\Content\Post;
use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Poll\Comment;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Repository\BookmarkRepository;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupQuestionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionAnswerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadMicropetitionAnswerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadMicropetitionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadMicropetitionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BookmarkControllerTest
 * @package Civix\ApiBundle\Tests\Controller
 *
 * @author Habibillah <habibillah@gmail.com>
 */
class BookmarkControllerTest extends WebTestCase
{
    private  $userToken;

    /** @var  Petition[] */
    private $petitions;

    /** @var  \Civix\CoreBundle\Entity\Micropetitions\Answer[] */
    private $petitionAnswers;

    /** @var  \Civix\CoreBundle\Entity\Micropetitions\Comment[] */
    private $petitionComments;

    /** @var Question[] */
    private $questions;

    /** @var  Answer[] */
    private $questionAnswers;

    /** @var  Comment[] */
    private $questionComments;

    /** @var  Post[] */
    private $posts;

    /** @var User */
    private $user;

    /**
     * @author Habibillah <habibillah@gmail.com>
     */
	public function setUp()
	{
        /** @var AbstractExecutor $fixtures */
        $fixtures = $this->loadFixtures([LoadUserData::class,
            LoadMicropetitionData::class,
            LoadMicropetitionAnswerData::Class,
            LoadMicropetitionCommentData::class,
            LoadGroupQuestionData::class,
            LoadQuestionAnswerData::class,
            LoadQuestionCommentData::class,
            LoadPostData::class]);

        $reference = $fixtures->getReferenceRepository();

        $this->user = $reference->getReference('testuserbookmark1');

        $this->petitions = [
            $reference->getReference('micropetition_1'),
            $reference->getReference('micropetition_2'),
            $reference->getReference('micropetition_3')
        ];

        $this->petitionAnswers = [
            $reference->getReference('micropetition_answer_1'),
            $reference->getReference('micropetition_answer_2'),
            $reference->getReference('micropetition_answer_3')
        ];

        $this->petitionComments = [
            $reference->getReference('micropetition_comment_1'),
            $reference->getReference('micropetition_comment_2'),
            $reference->getReference('micropetition_comment_3')
        ];

        $this->questions = [
            $reference->getReference('group_question_1'),
            $reference->getReference('group_question_2'),
            $reference->getReference('group_question_3'),
            $reference->getReference('group_question_4')
        ];

        $this->questionAnswers = [
            $reference->getReference('question_answer_1'),
            $reference->getReference('question_answer_2'),
            $reference->getReference('question_answer_3')
        ];

        $this->questionComments = [
            $reference->getReference('question_comment_1'),
            $reference->getReference('question_comment_4')
        ];

        $this->posts = [
            $reference->getReference('post_1'),
            $reference->getReference('post_2'),
            $reference->getReference('post_3')
        ];

        /** @var BookmarkRepository $repo */
        $repo = $this->getContainer()->get('doctrine')->getRepository(Bookmark::class);

        foreach ($this->petitions as $item)
            $repo->save(Bookmark::TYPE_PETITION, $this->user, $item->getId());

        foreach ($this->petitionAnswers as $item)
            $repo->save(Bookmark::TYPE_PETITION_ANSWER, $this->user, $item->getId());

        foreach ($this->petitionComments as $item)
            $repo->save(Bookmark::TYPE_PETITION_COMMENT, $this->user, $item->getId());

        foreach ($this->questions as $item)
            $repo->save(Bookmark::TYPE_POLL, $this->user, $item->getId());

        foreach ($this->questionAnswers as $item)
            $repo->save(Bookmark::TYPE_POLL_ANSWER, $this->user, $item->getId());

        foreach ($this->questionComments as $item)
            $repo->save(Bookmark::TYPE_POLL_COMMENT, $this->user, $item->getId());

        foreach ($this->posts as $item)
            $repo->save(Bookmark::TYPE_POLL_COMMENT, $this->user, $item->getId());

        if (empty($this->userToken))
            $this->userToken = $this->getLoginToken($this->user);
	}

    /**
     * @author Habibillah <habibillah@gmail.com>
     */
    public function testIndexAction()
    {
        $client = static::createClient();
        $client->setServerParameter("HTTP_Token", $this->userToken);

        $client->request('GET', '/api/bookmarks/list');
        $this->isSuccessful($client->getResponse(), false);
        $this->assertStatusCode(404, $client);

        $client->request('GET', '/api/bookmarks/list/' . Bookmark::TYPE_PETITION);
        $content = $client->getResponse()->getContent();
        $this->assertEquals($this->toJsonObject($this->petitions), $this->buildResponse($content));

        $client->request('GET', '/api/bookmarks/list/' . Bookmark::TYPE_PETITION_ANSWER);
        $content = $client->getResponse()->getContent();
        $this->assertEquals($this->toJsonObject($this->petitionAnswers), $this->buildResponse($content));

        $client->request('GET', '/api/bookmarks/list/' . Bookmark::TYPE_PETITION_COMMENT);
        $content = $client->getResponse()->getContent();
        $this->assertEquals($this->toJsonObject($this->petitionComments), $this->buildResponse($content));

        $client->request('GET', '/api/bookmarks/list/' . Bookmark::TYPE_POLL);
        $content = $client->getResponse()->getContent();
        $this->assertEquals($this->toJsonObject($this->questions), $this->buildResponse($content));

        $client->request('GET', '/api/bookmarks/list/' . Bookmark::TYPE_POLL_ANSWER);
        $content = $client->getResponse()->getContent();
        $this->assertEquals($this->toJsonObject($this->questionAnswers), $this->buildResponse($content));

        $client->request('GET', '/api/bookmarks/list/' . Bookmark::TYPE_POLL_COMMENT);
        $content = $client->getResponse()->getContent();
        $this->assertEquals($this->toJsonObject($this->questionComments), $this->buildResponse($content));

        $client->request('GET', '/api/bookmarks/list/' . Bookmark::TYPE_POST);
        $content = $client->getResponse()->getContent();
        $this->assertEquals($this->toJsonObject($this->posts), $this->buildResponse($content));
    }

    private function toJsonObject($object)
    {
        $request = Request::create('http://localhost:80');
        $this->getContainer()->enterScope('request');
        $this->getContainer()->set('request', $request, 'request');

        $json = $this->jmsSerialization($object, ['api-bookmarks', 'api-post', 'api-poll',
            'api-poll-public', 'api-petitions-info', 'api-petitions-list', 'api-comments',
            'api-answer', 'api-answers-list', 'api-leader-answers']);

        return (array)json_decode($json);
    }

    private function buildResponse($content)
    {
        $data = [];
        foreach (json_decode($content)->items as $item)
            $data[] = $item->detail;

        return $data;
    }
}
