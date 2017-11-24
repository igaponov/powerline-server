<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Service\Authy;
use Civix\CoreBundle\Service\FacebookApi;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Issue\PM533;
use Civix\CoreBundle\Service\CiceroApi;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowerData;
use GuzzleHttp\Command\Result;
use libphonenumber\PhoneNumber;
use Symfony\Bundle\FrameworkBundle\Client;

class ProfileControllerTest extends WebTestCase
{
	private const API_ENDPOINT = '/api/profile/';

    /**
     * @var Client
     */
	private $client;

	public function setUp(): void
    {
		$this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

	public function tearDown(): void
    {
		$this->client = NULL;
        parent::tearDown();
    }
	
	public function testUpdateProfile(): void
    {
        $this->loadFixtures([
            LoadUserData::class,
        ]);
        $avatar = base64_encode(file_get_contents(__DIR__.'/../data/image.png'));
        $params = [
            'first_name' => 'new-firstName',
            'last_name' => 'new-lastName',
            'email' => 'new@email.com',
            'zip' => 'new-zip',
            'birth' => '11/11/2011',
            'address1' => 'new-address1',
            'address2' => 'new-address2',
            'city' => 'new-city',
            'state' => 'new-state',
            'country' => 'DZ',
            'phone' => '+1111111111',
            'facebook_link' => 'new-facebookLink',
            'twitter_link' => 'new-twitterLink',
            'race' => 'new-race',
            'sex' => 'new-sex',
            'orientation' => 'new-orientation',
            'marital_status' => 'new-maritalStatus',
            'religion' => 'new-religion',
            'employment_status' => 'new-employmentStatus',
            'income_level' => 'new-incomeLevel',
            'education_level' => 'new-educationLevel',
            'party' => 'new-party',
            'philosophy' => 'new-philosophy',
            'donor' => 'new-donor',
            'bio' => 'new-bio',
            'slogan' => 'new-slogan',
            'interests' => ['new-interest1', 'new-interest2'],
            'registration' => 'new-registration',
        ];
        $client = $this->client;
        $service = $this->getMockBuilder(CiceroApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepresentativesByLocation'])
            ->getMock();
        $service->expects($this->once())
            ->method('getRepresentativesByLocation');
        $client->getContainer()->set('civix_core.cicero_api', $service);
        $service = $this->getMockBuilder(Authy::class)
            ->disableOriginalConstructor()
            ->setMethods(['checkVerification'])
            ->getMock();
        $service->expects($this->once())
            ->method('checkVerification')
            ->with($this->isInstanceOf(PhoneNumber::class), '135246')
            ->willReturn(new Result(['success' => true]));
        $client->getContainer()->set('civix_core.service.authy', $service);
        $client->request('POST', self::API_ENDPOINT.'update', [], [], ['HTTP_Authorization' => 'Bearer type="user" token="user1"'], json_encode(array_merge($params, ['avatar_file_name' => $avatar, 'code' => '135246'])));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        foreach (array_keys($params) as $key) {
            $this->assertEquals($params[$key], $data[$key], $key);
        }
        $storage = $client->getContainer()->get('civix_core.storage.array');
        $files = $storage->getFiles('avatar_image_fs');
        $this->assertCount(1, $files);
        $this->assertEquals(
            'https://powerline-dev.imgix.net/avatars/'.key($files).'?ixlib=php-1.1.0',
            $data['avatar_file_name']
        );
    }

	public function testUpdateProfileWithContentTypeTextPlain(): void
    {
        $this->loadFixtures([
            LoadUserData::class,
        ]);
        $avatar = base64_encode(file_get_contents(__DIR__.'/../data/image.png'));
        $params = [
            'first_name' => 'new-firstName',
            'last_name' => 'new-lastName',
            'email' => 'new@email.com',
            'zip' => 'new-zip',
            'birth' => '11/11/2011',
            'address1' => 'new-address1',
            'address2' => 'new-address2',
            'city' => 'new-city',
            'state' => 'new-state',
            'country' => 'AO',
            'facebook_link' => 'new-facebookLink',
            'twitter_link' => 'new-twitterLink',
            'race' => 'new-race',
            'sex' => 'new-sex',
            'orientation' => 'new-orientation',
            'marital_status' => 'new-maritalStatus',
            'religion' => 'new-religion',
            'employment_status' => 'new-employmentStatus',
            'income_level' => 'new-incomeLevel',
            'education_level' => 'new-educationLevel',
            'party' => 'new-party',
            'philosophy' => 'new-philosophy',
            'donor' => 'new-donor',
            'bio' => 'new-bio',
            'slogan' => 'new-slogan',
            'interests' => ['new-interest1', 'new-interest2'],
            'registration' => 'new-registration',
        ];
        $client = $this->client;
        $service = $this->getMockBuilder(CiceroApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepresentativesByLocation'])
            ->getMock();
        $service->expects($this->once())
            ->method('getRepresentativesByLocation');
        $client->getContainer()->set('civix_core.cicero_api', $service);
        $service = $this->getMockBuilder(Authy::class)
            ->disableOriginalConstructor()
            ->setMethods(['checkVerification'])
            ->getMock();
        $service->expects($this->never())
            ->method('checkVerification');
        $client->getContainer()->set('civix_core.service.authy', $service);
        $client->request('POST', self::API_ENDPOINT.'update', [], [], [
            'HTTP_Authorization' => 'Bearer type="user" token="user1"',
            'CONTENT_TYPE' => 'text/plain;charset=UTF-8',
            ], json_encode(array_merge($params, ['avatar_file_name' => $avatar, 'code' => '135246'])));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        foreach (array_keys($params) as $key) {
            $this->assertEquals($params[$key], $data[$key], $key);
        }
        $storage = $client->getContainer()->get('civix_core.storage.array');
        $files = $storage->getFiles('avatar_image_fs');
        $this->assertCount(1, $files);
        $this->assertEquals(
            'https://powerline-dev.imgix.net/avatars/'.key($files).'?ixlib=php-1.1.0',
            $data['avatar_file_name']
        );
    }

	public function testUpdateProfileWithErrors(): void
    {
        $this->loadFixtures([
            LoadUserData::class,
        ]);
        $params = [
            'first_name' => '',
            'last_name' => '',
            'email' => 'user2@example.com',
            'zip' => '',
            'country' => 'United States',
            'phone' => '+1111111111',
            'code' => '135246',
        ];
        $client = $this->client;
        $service = $this->getMockBuilder(Authy::class)
            ->disableOriginalConstructor()
            ->setMethods(['checkVerification'])
            ->getMock();
        $service->expects($this->once())
            ->method('checkVerification')
            ->with($this->isInstanceOf(PhoneNumber::class), '135246')
            ->willReturn(new Result(['success' => false, 'message' => 'Invalid code.']));
        $client->getContainer()->set('civix_core.service.authy', $service);
		$client->request('POST', self::API_ENDPOINT.'update', [], [], ['HTTP_Authorization' => 'Bearer type="user" token="user1"'], json_encode($params));
		$response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        /** @var array $errors */
        $errors = $data['errors'];
        $this->assertCount(6, $errors);
        foreach ($errors as $error) {
            switch ($error['property']) {
                case 'first_name':
                    $message = 'This value should not be blank.';
                    break;
                case 'last_name':
                    $message = 'This value should not be blank.';
                    break;
                case 'zip':
                    $message = 'This value should not be blank.';
                    break;
                case 'country':
                    $message = 'This value is not a valid country.';
                    break;
                case 'code':
                    $message = 'Invalid code.';
                    break;
                case null:
                    $message = 'This value is already used.';
                    break;
                default:
                    $this->fail("Property {$error['property']} should not have an error");
                    return;
            }
            $this->assertEquals($message, $error['message']);
        }
    }

	public function testUpdateWithSameEmailAndPhoneAndAvatar(): void
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            PM533::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $params = [
            'email' => 'user1@example.com',
            'first_name' => 'new-firstName',
            'last_name' => 'new-lastName',
            'zip' => 'new-zip',
            'avatar_file_name' => 'https://powerline-dev.imgix.net/avatars/1.jpg?ixlib=php-1.1.0',
        ];
        $client = $this->client;
        $service = $this->getMockBuilder(Authy::class)
            ->disableOriginalConstructor()
            ->setMethods(['checkVerification'])
            ->getMock();
        $service->expects($this->never())
            ->method('checkVerification');
        $client->getContainer()->set('civix_core.service.authy', $service);
        $client->request('POST', self::API_ENDPOINT.'update', [], [], ['HTTP_Authorization' => 'Bearer type="user" token="user1"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($params['email'], $data['email']);
        $this->assertEquals(
            'https://powerline-dev.imgix.net/avatars/'.$user->getAvatarFileName().'?ixlib=php-1.1.0',
            $data['avatar_file_name']
        );
    }

	public function testUpdateSettings(): void
    {
        $this->loadFixtures([
            LoadUserData::class,
        ]);
        $params = [
            'do_not_disturb' => true,
            'is_notif_questions' => false,
            'is_notif_discussions' => false,
            'is_notif_messages' => false,
            'is_notif_micro_following' => false,
            'is_notif_micro_group' => false,
            'is_notif_scheduled' => false,
            'is_notif_own_post_changed' => false,
            'followed_do_not_disturb_till' => '2016-10-15T15:33:50+0000',
            'scheduled_from' => 'Tue, 18 Oct 2016 15:33:50 +0000',
            'scheduled_to' => 'Fri, 21 Oct 2016 15:33:50 +0000',
        ];
        $client = $this->client;
		$client->request('POST', self::API_ENDPOINT.'settings', [], [], ['HTTP_Authorization' => 'Bearer user1'], json_encode($params));
		$response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        foreach (array_keys($params) as $key) {
            $this->assertEquals($params[$key], $data[$key], $key);
        }
    }

	public function testGetMyFacebookFriends(): void
    {
        $this->loadFixtures([
            LoadUserFollowerData::class,
        ]);
        $params = ['fb_followertest', 'fb_userfollowtest2'];
        $client = $this->client;
		$client->request('POST', self::API_ENDPOINT.'facebook-friends', [], [], ['HTTP_Authorization' => 'Bearer type="user" token="user1"'], json_encode($params));
		$response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        /** @var array $data */
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data);
        foreach ($data as $item) {
            $this->assertThat(
                $item['username'],
                $this->logicalOr('followertest', 'userfollowtest2')
            );
        }
    }

	public function testLinkToFacebook(): void
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_2');
        $params = [
            'facebook_id' => 'id_00001',
            'facebook_token' => 'xxx_token',
            'avatar_file_name' => base64_encode(file_get_contents(__DIR__.'/../data/image.png')),
        ];
        $client = $this->client;
        $service = $this->getMockBuilder(FacebookApi::class)
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->once())
            ->method('checkFacebookToken')
            ->with($params['facebook_token'], $params['facebook_id'])
            ->willReturn(true);
        $client->getContainer()->set('civix_core.facebook_api', $service);
		$client->request('POST', self::API_ENDPOINT.'link-to-facebook', [], [], ['HTTP_Authorization' => 'Bearer type="user" token="user2"'], json_encode($params));
		$response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($params['facebook_id'], $data['facebook_id']);
        $files = $client->getContainer()
            ->get('civix_core.storage.array')
            ->getFiles('avatar_image_fs');
        $this->assertCount(1, $files);
        $this->assertNotEquals($user->getAvatarFileName(), $data['avatar_file_name']);
    }
}
