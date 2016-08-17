<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Activities\UserPetition;
use Civix\CoreBundle\Entity\Activities\Petition;
use Civix\CoreBundle\Entity\Activities\Question;
use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\Bookmark;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Repository\BookmarkRepository;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadActivityData;
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

    /** @var UserPetition[] */
    private $microPetitions;

    /** @var  Question[] */
    private $questions;

    /** @var User */
    private $user;

    /**
     * @author Habibillah <habibillah@gmail.com>
     */
	public function setUp()
	{
        /** @var AbstractExecutor $fixtures */
        $fixtures = $this->loadFixtures([LoadUserData::class, LoadActivityData::class]);

        $reference = $fixtures->getReferenceRepository();

        $this->user = $reference->getReference('testuserbookmark1');

        $this->petitions = [
            $reference->getReference('activity_petition')
        ];

        $this->microPetitions = [
            $reference->getReference('activity_micropetition')
        ];

        $this->questions = [
            $reference->getReference('activity_question')
        ];

        /** @var BookmarkRepository $repo */
        $repo = $this->getContainer()->get('doctrine')->getRepository(Bookmark::class);

        foreach ($this->petitions as $item)
            $repo->save(Activity::TYPE_PETITION, $this->user, $item->getId());

        foreach ($this->microPetitions as $item)
            $repo->save(Activity::TYPE_USER_PETITION, $this->user, $item->getId());

        foreach ($this->questions as $item)
            $repo->save(Activity::TYPE_QUESTION, $this->user, $item->getId());

        if (empty($this->userToken))
            $this->userToken = $this->getLoginToken($this->user);
	}

    protected function tearDown()
    {
        $this->user = null;
        $this->petitions = [];
        $this->microPetitions = [];
        $this->questions = [];
        $this->userToken = null;
        parent::tearDown();
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

        $client->request('GET', '/api/bookmarks/list/' . Activity::TYPE_PETITION);
        $content = $client->getResponse()->getContent();
        $this->assertEquals($this->toJsonObject($this->petitions), $this->buildResponse($content));

        $client->request('GET', '/api/bookmarks/list/' . Activity::TYPE_USER_PETITION);
        $content = $client->getResponse()->getContent();
        $this->assertEquals($this->toJsonObject($this->microPetitions), $this->buildResponse($content));

        $client->request('GET', '/api/bookmarks/list/' . Activity::TYPE_QUESTION);
        $content = $client->getResponse()->getContent();
        $this->assertEquals($this->toJsonObject($this->questions), $this->buildResponse($content));
    }

    private function toJsonObject($object)
    {
        $request = Request::create('http://localhost:80');
        $this->getContainer()->enterScope('request');
        $this->getContainer()->set('request', $request, 'request');

        $json = $this->jmsSerialization($object, ['api-bookmarks', 'api-activities']);

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
