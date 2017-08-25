<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFollowerTestData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupFollowerTestData;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;

class FollowControllerTest extends WebTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    private $followerToken;

    /** @var User */
    private $follower;

    /** @var User */
    private $user1;

    /** @var User */
    private $user2;

    /** @var User */
    private $user3;

    /** @var  Group */
    private $secretGroup;

    /** @var  Group */
    private $privateGroup;

    protected function setUp()
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();

        /** @var AbstractExecutor $fixtures */
        $fixtures = $this->loadFixtures([
            LoadUserData::class,
            LoadGroupFollowerTestData::class,
            LoadUserGroupFollowerTestData::class
        ]);
        $reference = $fixtures->getReferenceRepository();

        $this->follower = $reference->getReference('followertest');
        $this->user1 = $reference->getReference('userfollowtest1');
        $this->user2 = $reference->getReference('userfollowtest2');
        $this->user3 = $reference->getReference('userfollowtest3');

        $this->secretGroup = $reference->getReference('testfollowsecretgroups');
        $this->privateGroup = $reference->getReference('testfollowprivategroups');

        if (empty($this->followerToken))
            $this->followerToken = $this->getLoginToken($this->follower);

    }

    protected function tearDown()
    {
        $this->em = null;
        $this->follower = null;
        $this->followerToken = null;
        $this->user1 = null;
        $this->user2 = null;
        $this->user3 = null;
        $this->secretGroup = null;
        $this->privateGroup = null;
        parent::tearDown();
    }

    /**
     * Test logedin user to follow a user
     * 
     * @author Habibillah <habibillah@gmail.com>
     */
    public function testPostAction()
    {
        $client = static::createClient();
        $client->setServerParameter('HTTP_Token', $this->followerToken);

        $follow = (new UserFollow())
            ->setUser($this->user1)
            ->setDoNotDisturbTill(new \DateTime('+2 hours'));
        $content = $this->jmsSerialization($follow, ['api-follow-create']);

        $client->request('POST', '/api/follow/', [], [], [], $content);

        $response = $client->getResponse();
        $result = json_decode($response->getContent());

        $this->assertSame(201, $response->getStatusCode(), $response->getContent());
        $this->assertJson($response->getContent());
        $this->assertEquals($this->follower->getId(), $result->follower->id);
        $this->assertEquals($this->user1->getId(), $result->user->id);

        /** @var UserFollow[] $userFollow1 */
        $userFollow1 = $this->em->getRepository(UserFollow::class)->findBy(array('user' => $this->user1));

        $this->assertCount(1, $userFollow1);
        $this->assertSame($this->follower->getId(), $userFollow1[0]->getFollower()->getId());
    }

    /**
     * Test to follow member of secret group
     *
     * @author Habibillah <habibillah@gmail.com>
     */
    public function testFollowSecretGroup()
    {
        $client = static::createClient();
        $client->setServerParameter("HTTP_Token", $this->followerToken);
        $client->request('POST', "/api/follow/group/{$this->secretGroup->getId()}");

        $response = $client->getResponse();

        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * Test to follow private group
     *
     * @author Habibillah <habibillah@gmail.com>
     */
    public function testFollowPrivateGroup()
    {
        $client = static::createClient();
        $client->setServerParameter("HTTP_Token", $this->followerToken);

        $client->request('POST', "/api/follow/group/{$this->privateGroup->getId()}");

        $response = $client->getResponse();

        $this->assertSame(201, $response->getStatusCode());

        /** @var UserFollow[] $userFollow1 */
        $userFollow1 = $this->em->getRepository(UserFollow::class)->findBy(array('user' => $this->user1));

        $this->assertCount(1, $userFollow1);
        $this->assertSame($this->follower->getId(), $userFollow1[0]->getFollower()->getId());

        /** @var UserFollow[] $userFollow2 */
        $userFollow2 = $this->em->getRepository(UserFollow::class)->findBy(array('user' => $this->user2));

        $this->assertCount(1, $userFollow2);
        $this->assertSame($this->follower->getId(), $userFollow2[0]->getFollower()->getId());

        /** @var UserFollow[] $userFollow3 */
        $userFollow3 = $this->em->getRepository(UserFollow::class)->findBy(array('user' => $this->user3));

        $this->assertCount(1, $userFollow3);
        $this->assertSame($this->follower->getId(), $userFollow3[0]->getFollower()->getId());
    }
}