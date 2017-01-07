<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Activities\Post;
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
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BookmarkControllerTest
 * @package Civix\ApiBundle\Tests\Controller
 *
 * @author Habibillah <habibillah@gmail.com>
 */
class BookmarkControllerTest extends WebTestCase
{
    private $userToken;

    /** @var Petition[] */
    private $petitions;

    /** @var UserPetition[] */
    private $microPetitions;

    /** @var Question[] */
    private $questions;

    /** @var Post[] */
    private $posts;

    /** @var User */
    private $user;

    private $bookmarks = [];

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
            $reference->getReference('activity_user_petition')
        ];

        $this->questions = [
            $reference->getReference('activity_question')
        ];

        $this->posts = [
            $reference->getReference('activity_post')
        ];

        /** @var BookmarkRepository $repo */
        $repo = $this->getContainer()->get('doctrine')->getRepository(Bookmark::class);

        foreach ($this->petitions as $item)
            $this->bookmarks[] = $repo->save(Activity::TYPE_PETITION, $this->user, $item->getId());

        foreach ($this->microPetitions as $item)
            $this->bookmarks[] = $repo->save(Activity::TYPE_USER_PETITION, $this->user, $item->getId());

        foreach ($this->questions as $item)
            $this->bookmarks[] = $repo->save(Activity::TYPE_QUESTION, $this->user, $item->getId());

        foreach ($this->posts as $item)
            $this->bookmarks[] = $repo->save(Activity::TYPE_POST, $this->user, $item->getId());

        if (empty($this->userToken))
            $this->userToken = $this->getLoginToken($this->user);
	}

    protected function tearDown()
    {
        $this->user = null;
        $this->petitions = [];
        $this->microPetitions = [];
        $this->questions = [];
        $this->posts = [];
        $this->bookmarks = [];
        $this->userToken = null;
        parent::tearDown();
    }

    /**
     * @author Habibillah <habibillah@gmail.com>
     */
    public function testIndexAction()
    {
        $client = $this->makeClient();
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

        $client->request('GET', '/api/bookmarks/list/' . Activity::TYPE_POST);
        $content = $client->getResponse()->getContent();
        $this->assertEquals($this->toJsonObject($this->posts), $this->buildResponse($content));
    }

    public function testAddAction()
    {
        $client = static::createClient();
        $client->setServerParameter("HTTP_Token", $this->userToken);

        $client->request('POST', '/api/bookmarks/add/' . Activity::TYPE_PETITION . '/' . $this->petitions[0]->getId());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($this->petitions[0]->getId(), $content['item_id']);
        $this->assertEquals(Activity::TYPE_PETITION, $content['type']);

        $client->request('POST', '/api/bookmarks/add/' . Activity::TYPE_USER_PETITION . '/' . $this->microPetitions[0]->getId());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($this->microPetitions[0]->getId(), $content['item_id']);
        $this->assertEquals(Activity::TYPE_USER_PETITION, $content['type']);

        $client->request('POST', '/api/bookmarks/add/' . Activity::TYPE_QUESTION . '/' . $this->questions[0]->getId());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($this->questions[0]->getId(), $content['item_id']);
        $this->assertEquals(Activity::TYPE_QUESTION, $content['type']);

        $client->request('POST', '/api/bookmarks/add/' . Activity::TYPE_POST . '/' . $this->posts[0]->getId());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($this->posts[0]->getId(), $content['item_id']);
        $this->assertEquals(Activity::TYPE_POST, $content['type']);
    }

    public function testRemoveAction()
    {
        $client = static::createClient();
        $client->setServerParameter("HTTP_Token", $this->userToken);
        $em = $this->getContainer()->get('doctrine')->getManager();
        foreach ($this->bookmarks as $bookmark) {
            $client->request('DELETE', '/api/bookmarks/remove/' . $bookmark->getId());
            $this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
            $this->assertNull($em->refresh($bookmark));
        }
    }

    private function toJsonObject($object)
    {
        $json = $this->jmsSerialization($object, ['api-bookmarks', 'api-activities', 'activity-list']);

        $array = json_decode($json, true);
        unset($array[0]['group']['group_type_label']);

        return $array;
    }

    private function buildResponse($content)
    {
        $data = [];
        foreach (json_decode($content, true)['items'] as $item)
            $data[] = $item['detail'];

        return $data;
    }
}
