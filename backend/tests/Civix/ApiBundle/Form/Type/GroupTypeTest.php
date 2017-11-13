<?php

namespace Tests\Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\Type\GroupType;
use Civix\CoreBundle\Entity\Group;
use Faker\Factory;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Tests\Civix\ApiBundle\FormIntegrationTestCase;

/**
 * @property ExecutionContextInterface $context
 */
class GroupTypeTest extends FormIntegrationTestCase
{
    /**
     * @var UniqueEntityValidator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $uniqueEntity;

    public function testSubmitValidData(): void
    {
        $faker = Factory::create();
        $formData = [
            'manager_first_name' => $faker->firstName,
            'manager_last_name' => $faker->lastName,
            'manager_email' => $faker->email,
            'manager_phone' => $faker->phoneNumber,
            'official_type' => $faker->randomElement(Group::getOfficialTypes()),
            'official_name' => $faker->company,
            'official_description' => $faker->text,
            'acronym' => $faker->word,
            'official_address' => $faker->address,
            'official_city' => $faker->city,
            'official_state' => $faker->toUpper($faker->lexify('??')),
            'transparency' => $faker->randomElement(Group::getTransparencyStates()),
            'conversation_view_limit' => $faker->numberBetween(1),
        ];

        $form = $this->factory->create(GroupType::class, null, ['validation_groups' => 'user-registration']);

        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $data = $form->getData();
        $this->assertInstanceOf(Group::class, $data);
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
        $form = $this->factory->create(GroupType::class, null, ['validation_groups' => 'user-registration']);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertErrors($errors, $form);
    }

    public function getErrors(): array
    {
        return [
            'empty' => [
                [
                    'official_name' => '',
                    'official_type' => '',
                    'transparency' => '',
                ],
                [
                    'official_name' => ['This value should not be blank.'],
                    'official_type' => ['This value should not be blank.'],
                    'transparency' => ['This value should not be blank.'],
                ],
            ],
            'invalid' => [
                [
                    'official_name' => 'X',
                    'official_type' => 'Y',
                    'transparency' => 'Z',
                    'conversation_view_limit' => 0,
                ],
                [
                    'official_type' => ['The value you selected is not a valid choice.'],
                    'transparency' => ['The value you selected is not a valid choice.'],
                    'conversation_view_limit' => ['This value should be greater than or equal to "1".'],
                ],
            ],
        ];
    }

    public function testSubmitDuplicatedInvalidData(): void
    {
        $callback = function (Group $entity, UniqueEntity $constraint) {
            $this->context->buildViolation($constraint->message)
                ->atPath('name')
                ->setParameter('{{ value }}', $entity->getOfficialName())
                ->setInvalidValue($entity->getOfficialName())
                ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
                ->addViolation();
        };
        $this->uniqueEntity
            ->expects($this->once())
            ->method('validate')
            ->willReturnCallback($callback->bindTo($this->uniqueEntity, $this->uniqueEntity));

        $form = $this->factory->create(GroupType::class, null, ['validation_groups' => 'user-registration']);
        $form->submit([
            'official_name' => 'Test name',
            'official_type' => 'Educational',
            'transparency' => 'public',
        ]);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertErrors(['This value is already used.'], $form);
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
