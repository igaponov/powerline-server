<?php

namespace Tests\Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\Type\RegistrationType;
use Civix\CoreBundle\Entity\User;
use Faker\Factory;
use libphonenumber\PhoneNumber;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Tests\Civix\ApiBundle\FormIntegrationTestCase;

class RegistrationTypeTest extends FormIntegrationTestCase
{
    /**
     * @var UniqueEntityValidator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $uniqueEntity;

    public function testSubmitValidData(): void
    {
        $faker = Factory::create();
        $phone = '+41446681800';
        $formData = [
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
            'username' => $faker->userName,
            'email' => 'test@eamil.com',
            'country' => 'US',
            'zip' => $faker->numerify('######'),
        ];

        $form = $this->factory->create(RegistrationType::class);

        $form->submit(array_merge($formData, ['phone' => $phone]));
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        /** @var User $data */
        $data = $form->getData();
        $this->assertInstanceOf(User::class, $data);
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($formData as $key => $value) {
            $this->assertSame($value, $accessor->getValue($data, $key));
        }
        $this->assertInstanceOf(PhoneNumber::class, $data->getPhone());
    }

    /**
     * @param $formData
     * @param $errors
     * @dataProvider getErrors
     */
    public function testSubmitInvalidData($formData, $errors): void
    {
        $form = $this->factory->create(RegistrationType::class);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertErrors($errors, $form);
    }

    public function getErrors(): array
    {
        return [
            'no data' => [
                [], [
                    'first_name' => ['This value should not be blank.'],
                    'last_name' => ['This value should not be blank.'],
                    'username' => ['This value should not be blank.'],
                    'email' => ['This value should not be blank.'],
                    'country' => ['This value should not be blank.'],
                    'zip' => ['This value should not be blank.'],
                    'phone' => ['This value should not be blank.'],
                ],
            ],
            'empty' => [
                [
                    'first_name' => '',
                    'last_name' => '',
                    'username' => '',
                    'email' => '',
                    'country' => '',
                    'zip' => '',
                ],
                [
                    'first_name' => ['This value should not be blank.'],
                    'last_name' => ['This value should not be blank.'],
                    'username' => ['This value should not be blank.'],
                    'email' => ['This value should not be blank.'],
                    'country' => ['This value should not be blank.'],
                    'zip' => ['This value should not be blank.'],
                    'phone' => ['This value should not be blank.'],
                ],
            ],
        ];
    }

    protected function getConstraints(): array
    {
        $this->uniqueEntity = $this->getMockBuilder(UniqueEntityValidator::class)
            ->disableOriginalConstructor()
            ->setMethods(['validate'])
            ->getMock();

        return [
            'doctrine.orm.validator.unique' => $this->uniqueEntity,
        ];
    }
}
