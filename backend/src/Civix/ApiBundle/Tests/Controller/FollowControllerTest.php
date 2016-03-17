<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\ApiBundle\Tests\WebTestCase;

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

    public function setUp()
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();;
    }

    /**
     * Test logedin user to follow a user
     */
    public function testPostAction()
    {
        $client = static::createClient();
        $client->setServerParameter("HTTP_Token", $this->followerToken);

        $follow = new UserFollow();
        $follow->setUser($this->user1);
        $content = $this->jmsSerialization($follow, ['api-follow-create']);

        $client->request('POST', '/api/follow/', [], [], [], $content);

        $response = $client->getResponse();
        $result = json_decode($response->getContent());

        $this->assertSame(201, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertEquals($this->follower->getId(), $result->follower->id);
        $this->assertEquals($this->user1->getId(), $result->user->id);

        /** @var UserFollow[] $userFollow1 */
        $userFollow1 = $this->em
            ->getRepository(UserFollow::class)
            ->findBy(array('user' => $this->user1));

        $this->assertCount(1, $userFollow1);
        $this->assertSame($this->follower->getId(), $userFollow1[0]->getFollower()->getId());
    }

    /**
     * Test to follow member of secret group
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
     */
    public function testFollowPrivateGroup()
    {
        $client = static::createClient();
        $client->setServerParameter("HTTP_Token", $this->followerToken);

        $client->request('POST', "/api/follow/group/{$this->privateGroup->getId()}");

        $response = $client->getResponse();

        $this->assertSame(201, $response->getStatusCode());

        /** @var UserFollow[] $userFollow1 */
        $userFollow1 = $this->em
            ->getRepository(UserFollow::class)
            ->findBy(array('user' => $this->user1));

        $this->assertCount(1, $userFollow1);
        $this->assertSame($this->follower->getId(), $userFollow1[0]->getFollower()->getId());

        /** @var UserFollow[] $userFollow2 */
        $userFollow2 = $this->em
            ->getRepository(UserFollow::class)
            ->findBy(array('user' => $this->user2));

        $this->assertCount(1, $userFollow2);
        $this->assertSame($this->follower->getId(), $userFollow2[0]->getFollower()->getId());

        /** @var UserFollow[] $userFollow3 */
        $userFollow3 = $this->em
            ->getRepository(UserFollow::class)
            ->findBy(array('user' => $this->user3));

        $this->assertCount(1, $userFollow3);
        $this->assertSame($this->follower->getId(), $userFollow3[0]->getFollower()->getId());
    }
}