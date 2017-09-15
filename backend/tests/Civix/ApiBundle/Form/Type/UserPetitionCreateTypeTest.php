<?php

namespace Tests\Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\Type\UserPetitionCreateType;
use Civix\CoreBundle\Entity\UserPetition;
use Faker\Factory;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Tests\Civix\ApiBundle\FormIntegrationTestCase;

class UserPetitionCreateTypeTest extends FormIntegrationTestCase
{
    public function testSubmitValidData(): void
    {
        $faker = Factory::create();
        $formData = [
            'title' => $faker->sentence,
            'body' => $faker->text,
            'is_outsiders_sign' => true,
            'organization_needed' => true,
        ];

        $form = $this->factory->create(UserPetitionCreateType::class);

        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $data = $form->getData();
        $this->assertInstanceOf(UserPetition::class, $data);
        $accessor = new PropertyAccessor();
        foreach ($formData as $key => $value) {
            $this->assertSame($value, $accessor->getValue($data, $key));
        }
    }

    /**
     * @param $formData
     * @param $errors
     * @dataProvider getErrors
     */
    public function testSubmitInvalidData($formData, $errors): void
    {
        $form = $this->factory->create(UserPetitionCreateType::class);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertErrors($errors, $form);
    }

    public function getErrors(): array
    {
        return [
            'no data' => [
                [], ['body' => ['This value should not be blank.']],
            ],
            'empty' => [
                [
                    'body' => '',
                ],
                [
                    'body' => ['This value should not be blank.'],
                ],
            ],
        ];
    }
}
