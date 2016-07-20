<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Bookmark;
use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Repository\BookmarkRepository;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupQuestionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionAnswerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadMicropetitionAnswerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadMicropetitionData;
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

    /** @var Question[] */
    private $questions;

    /** @var  Answer */
    private $questionAnswers;

    /** @var User */
    private $user;

    /** @var BookmarkRepository */
    private $repo;

    /**
     * @author Habibillah <habibillah@gmail.com>
     */
	public function setUp()
	{
        /** @var AbstractExecutor $fixtures */
        $fixtures = $this->loadFixtures([LoadUserData::class,
            LoadMicropetitionData::class,
            LoadMicropetitionAnswerData::Class,
            LoadGroupQuestionData::class,
            LoadQuestionAnswerData::class]);

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

        /** @var BookmarkRepository */
        $this->repo = $this->getContainer()->get('doctrine')->getRepository(Bookmark::class);

        foreach ($this->petitions as $item)
            $this->repo->save(Bookmark::TYPE_PETITION, $this->user, $item->getId());

        foreach ($this->petitionAnswers as $item)
            $this->repo->save(Bookmark::TYPE_PETITION_ANSWER, $this->user, $item->getId());

        foreach ($this->questions as $item)
            $this->repo->save(Bookmark::TYPE_POLL, $this->user, $item->getId());

        foreach ($this->questionAnswers as $item)
            $this->repo->save(Bookmark::TYPE_POLL_ANSWER, $this->user, $item->getId());

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
        $this->assertEquals($this->toJsonObject($this->petitions), $this->buildData($content));

        $client->request('GET', '/api/bookmarks/list/' . Bookmark::TYPE_PETITION_ANSWER);
        $content = $client->getResponse()->getContent();
        $this->assertEquals($this->toJsonObject($this->petitionAnswers), $this->buildData($content));

        $client->request('GET', '/api/bookmarks/list/' . Bookmark::TYPE_POLL);
        $content = $client->getResponse()->getContent();
        $this->assertEquals($this->toJsonObject($this->questions), $this->buildData($content));

        $client->request('GET', '/api/bookmarks/list/' . Bookmark::TYPE_POLL_ANSWER);
        $content = $client->getResponse()->getContent();
        $this->assertEquals($this->toJsonObject($this->questionAnswers), $this->buildData($content));
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

    private function buildData($content)
    {
        $data = [];
        foreach (json_decode($content)->items as $item)
            $data[] = $item->detail;

        return $data;
    }
}
