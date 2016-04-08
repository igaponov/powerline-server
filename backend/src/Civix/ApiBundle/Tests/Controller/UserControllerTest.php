<?php
namespace Civix\ApiBundle\Tests\Controller;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\ApiBundle\Tests\DataFixtures\ORM\LoadSuperuserData;

class UserControllerTest extends WebTestCase
{
	private $client = null;

	public function setUp()
	{
		// Creates a initial client
		$this->client = static::createClient();

		/** @var AbstractExecutor $fixtures */
		$fixtures = $this->loadFixtures([
				LoadUserData::class,
				LoadGroupData::class,
				LoadUserGroupData::class,
				LoadSuperuserData::class
		]);
		$reference = $fixtures->getReferenceRepository();
	}

	public function tearDown()
	{
		// Creates a initial client
		$this->client = NULL;
	}

	/**
	 * @todo Stub test for implement in future
	 */
	public function test()
	{
		$this->markTestIncomplete();
	}
}
