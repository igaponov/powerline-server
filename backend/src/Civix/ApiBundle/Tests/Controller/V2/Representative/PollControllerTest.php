<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Representative;

use Civix\ApiBundle\Tests\Controller\V2\PollControllerTestCase;
use Civix\CoreBundle\Entity\UserRepresentative;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserRepresentativeData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Representative\LoadRepresentativeQuestionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Stripe\LoadAccountRepresentativeData;
use Faker\Factory;

class PollControllerTest extends PollControllerTestCase
{
	const API_ENDPOINT = '/api/v2/representatives/{root}/polls';

    protected function getApiEndpoint()
    {
        return self::API_ENDPOINT;
    }

    public function testGetPollsWithWrongCredentialsThrowsException()
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
        /** @var UserRepresentative $representative */
        $representative = $repository->getReference('representative_wc');
        $this->getPollsWithWrongCredentialsThrowsException($representative);
	}

	public function testGetPollsIsOk()
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
        /** @var UserRepresentative $representative */
        $representative = $repository->getReference('representative_wc');
        $this->getPollsIsOk($representative, 'user3');
	}

    /**
     * @param $params
     * @dataProvider getFilters
     */
	public function testGetFilteredPollsIsOk($params)
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
        /** @var UserRepresentative $representative */
        $representative = $repository->getReference('representative_wc');
        $this->getFilteredPollsIsOk($representative, $params);
	}

	public function testCreatePollWithWrongCredentialsThrowsException()
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
        /** @var UserRepresentative $representative */
        $representative = $repository->getReference('representative_wc');
        $this->createPollWithWrongCredentialsThrowsException($representative, 'user1');
	}

	/**
	 * @param array $params
	 * @param array $errors
	 * @dataProvider getInvalidParams
	 */
	public function testCreatePollReturnsErrors($params, $errors)
	{
        $repository = $this->loadFixtures([
            LoadUserRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var UserRepresentative $representative */
        $representative = $repository->getReference('representative_jb');
        $this->createPollReturnsErrors($representative, $params, $errors);
	}

	/**
	 * @param $params
	 * @dataProvider getValidParams
	 */
	public function testCreatePollIsOk($params)
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
            LoadAccountRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var UserRepresentative $representative */
        $representative = $repository->getReference('representative_jb');
        $this->createPollIsOk($representative, $params);
	}

	public function testCreatePaymentRequestWithoutStripeAccountThrowsException()
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
        /** @var UserRepresentative $representative */
        $representative = $repository->getReference('representative_jb');
        $this->createPaymentRequestWithoutStripeAccountThrowsException($representative);
	}

	public function testCreatePollWithCorrectCredentials()
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
        /** @var UserRepresentative $representative */
        $representative = $repository->getReference('representative_jb');
        $this->createPollWithCorrectCredentials($representative, 'user1');
	}

	public function getValidParams()
	{
		$faker = Factory::create();
		return array_merge(
		    [
                'representative' => [
                    [
                        'type' => 'representative',
                        'subject' => $faker->sentence,
                    ]
                ],
            ], parent::getValidParams()
        );
	}
}
