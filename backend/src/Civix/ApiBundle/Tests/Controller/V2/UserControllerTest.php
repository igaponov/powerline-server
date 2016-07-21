<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Symfony\Bundle\FrameworkBundle\Client;

class UserControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/user';

    /**
     * @var Client
     */
    private $client = null;

    public function setUp()
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    public function tearDown()
    {
        $this->client = NULL;
        parent::tearDown();
    }

    public function testGetUserProfileIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($user->getFullName(), $data['full_name']);
        $this->assertEquals($user->getUsername(), $data['username']);
        $this->assertEquals($user->getEmail(), $data['email']);
        $this->assertEquals($user->getBirth()->format('m/d/Y'), $data['birth']);
        $this->assertEquals($user->getCountry(), $data['country']);
        $this->assertEquals($user->getPhone(), $data['phone']);
        $this->assertEquals($user->getSlogan(), $data['slogan']);
        $this->assertEquals($user->getBio(), $data['bio']);
        $this->assertEquals($user->getInterests(), $data['interests']);
        $this->assertEquals($user->getDoNotDisturb(), $data['do_not_disturb']);
        $this->assertEquals($user->getIsNotifQuestions(), $data['is_notif_questions']);
        $this->assertEquals($user->getIsNotifDiscussions(), $data['is_notif_discussions']);
        $this->assertEquals($user->getIsNotifMessages(), $data['is_notif_messages']);
        $this->assertEquals($user->getIsNotifMicroFollowing(), $data['is_notif_micro_following']);
        $this->assertEquals($user->getIsNotifMicroGroup(), $data['is_notif_micro_group']);
        $this->assertEquals($user->getIsNotifScheduled(), $data['is_notif_scheduled']);
        $this->assertEquals($user->getIsNotifOwnPostChanged(), $data['is_notif_own_post_changed']);
        $this->assertEquals($user->getIsRegistrationComplete(), $data['is_registration_complete']);
    }
}