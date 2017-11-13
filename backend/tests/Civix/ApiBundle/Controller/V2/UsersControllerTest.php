<?php
namespace Tests\Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\DiscountCode;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadDiscountCodeData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadKarmaData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowerData;
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Symfony\Bundle\FrameworkBundle\Client;

class UsersControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/users';

    /**
     * @var Client
     */
    private $client;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::bootFixtureLoader();
        self::$fixtureLoader->loadFixtures([
            LoadUserFollowerData::class,
            LoadKarmaData::class,
            LoadDiscountCodeData::class,
        ]);
    }

    public function setUp()
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    public function tearDown()
    {
        $this->client = NULL;
        parent::tearDown();
    }
    /**
     * @QueryCount(3)
     */
    public function testGetNotFollowingUserProfileIsOk()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        /** @var DiscountCode $code */
        $code = $repository->getReference('discount_code_1');
        $client = $this->client;
        /** @var EntityManagerInterface $em */
        $client->request('GET', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user2"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayNotHasKey('city', $data);
        $this->assertArrayNotHasKey('state', $data);
        $this->assertArrayHasKey('discount_codes', $data);
        $this->assertArrayHasKey('karma', $data);
        $this->assertEquals(63, $data['karma']);
        $this->assertEquals($user->getSlogan(), $data['slogan']);
        $this->assertEquals($user->getBio(), $data['bio']);
        $this->assertCount(1, $data['discount_codes']);
        $codeData = $data['discount_codes'][0];
        $this->assertCount(3, $codeData);
        $this->assertSame($code->getId(), $codeData['id']);
        $this->assertSame($code->getCode(), $codeData['code']);
    }

    /**
     * @QueryCount(3)
     */
    public function testGetFollowingUserProfileIsOk()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_2');
        /** @var DiscountCode $code */
        $code = $repository->getReference('discount_code_4');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('city', $data);
        $this->assertArrayHasKey('state', $data);
        $this->assertArrayHasKey('karma', $data);
        $this->assertArrayHasKey('discount_codes', $data);
        $this->assertEquals(0, $data['karma']);
        $this->assertEquals($user->getSlogan(), $data['slogan']);
        $this->assertEquals($user->getBio(), $data['bio']);
        $this->assertCount(1, $data['discount_codes']);
        $codeData = $data['discount_codes'][0];
        $this->assertCount(3, $codeData);
        $this->assertSame($code->getId(), $codeData['id']);
        $this->assertSame($code->getCode(), $codeData['code']);
        $this->assertNotEmpty($codeData['created_at']);
    }
}