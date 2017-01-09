<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Group;

use Civix\ApiBundle\Tests\Controller\V2\PollControllerTestCase;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupQuestionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupRepresentativesData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupSectionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Stripe\LoadAccountGroupData;
use Doctrine\DBAL\Connection;
use Faker\Factory;

class PollControllerTest extends PollControllerTestCase
{
	const API_ENDPOINT = '/api/v2/groups/{root}/polls';

    protected function getApiEndpoint()
    {
        return self::API_ENDPOINT;
    }

    public function testGetPollsWithWrongCredentialsThrowsException()
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference('group_3');
        $this->getPollsWithWrongCredentialsThrowsException($group);
	}

    /**
     * @param $fixtures
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForGetRequest
     */
	public function testGetPollsIsOk($fixtures, $user, $reference)
	{
        $repository = $this->loadFixtures(
            array_merge([LoadGroupQuestionData::class], $fixtures))->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference($reference);
        $this->getPollsIsOk($group, $user);
	}

    /**
     * @param $params
     * @dataProvider getFilters
     */
	public function testGetFilteredPollsIsOk($params)
	{
        $repository = $this->loadFixtures([
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference('group_3');
        $this->getFilteredPollsIsOk($group, $params);
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidPollCredentialsForUpdateRequest
     */
	public function testCreatePollWithWrongCredentialsThrowsException($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference($reference);
        $this->createPollWithWrongCredentialsThrowsException($group, $user);
	}

	/**
	 * @param array $params
	 * @param array $errors
	 * @dataProvider getInvalidParams
	 */
	public function testCreatePollReturnsErrors($params, $errors)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference('group_1');
        $this->createPollReturnsErrors($group, $params, $errors);
	}

	/**
	 * @param $params
	 * @dataProvider getValidParams
	 */
	public function testCreatePollIsOk($params)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupQuestionData::class,
            LoadAccountGroupData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference('group_1');
        $this->createPollIsOk($group, $params);
	}

	public function testCreatePaymentRequestWithoutStripeAccountThrowsException()
	{
        $repository = $this->loadFixtures([
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference('group_1');
        $this->createPaymentRequestWithoutStripeAccountThrowsException($group);
	}

    /**
     * @param $fixtures
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForUpdateRequest
     */
	public function testCreatePollWithCorrectCredentials($fixtures, $user, $reference)
	{
        $repository = $this->loadFixtures(
            array_merge([
                LoadGroupQuestionData::class,
                LoadGroupSectionData::class,
            ], $fixtures)
        )->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference($reference);
        $section1 = $repository->getReference('group_1_section_1');
        $section2 = $repository->getReference('group_1_section_2');
        $params = ['group_sections' => [$section2->getId(), $section1->getId()]];
        $data = $this->createPollWithCorrectCredentials($group, $user, $params);
		/** @var Connection $conn */
		$conn = $this->client->getContainer()->get('doctrine')->getConnection();
		$count = $conn->fetchColumn('SELECT COUNT(*) FROM poll_sections ps WHERE group_section_id IN (?, ?) AND question_id = ?', [$section1->getId(), $section2->getId(), $data['id']]);
		$this->assertEquals(2, $count);
	}

    public function testCreatePollWithInvalidGroupSection()
    {
        $errors = ['group_sections' => 'This value is not valid.'];
        $repository = $this->loadFixtures([
            LoadGroupQuestionData::class,
            LoadGroupSectionData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $section = $repository->getReference('group_3_section_1');
        $client = $this->client;
        $uri = str_replace('{root}', $group->getId(), self::API_ENDPOINT);
        $params = [
            'type' => 'group',
            'subject' => 'subj',
            'group_sections' => [$section->getId()],
        ];
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
        $this->assertResponseHasErrors($client->getResponse(), $errors);
    }

	public function getValidParams()
	{
		$faker = Factory::create();
		return array_merge(
		    [
                'group' => [
                    [
                        'type' => 'group',
                        'subject' => $faker->sentence,
                    ]
                ],
            ], []
        );
	}

    public function getInvalidPollCredentialsForUpdateRequest()
    {
        return [
            'member' => ['user4', 'group_3'],
            'outlier' => ['user1', 'group_3'],
        ];
    }

    public function getValidPollCredentialsForUpdateRequest()
    {
        return [
            'owner' => [[], 'user1', 'group_1'],
            'manager' => [[LoadGroupManagerData::class], 'user2', 'group_1'],
            'representative' => [[LoadGroupRepresentativesData::class], 'user3', 'group_1'],
        ];
    }

    public function getValidPollCredentialsForGetRequest()
    {
        return [
            'owner' => [[], 'user3', 'group_3'],
            'manager' => [[LoadGroupManagerData::class], 'user2', 'group_3'],
            'member' => [[LoadUserGroupData::class], 'user4', 'group_3'],
            'representative' => [[LoadGroupRepresentativesData::class], 'user3', 'group_1'],
        ];
    }
}
