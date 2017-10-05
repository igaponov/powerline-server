<?php

namespace Tests\Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\Type\DeviceType;
use Civix\Component\Notification\Model\Device;
use Civix\CoreBundle\Entity\User;
use Faker\Factory;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Tests\Civix\ApiBundle\FormIntegrationTestCase;

/**
 * @property ExecutionContextInterface $context
 */
class DeviceTypeTest extends FormIntegrationTestCase
{
    /**
     * @var UniqueEntityValidator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $uniqueEntity;

    public function testSubmitValidData(): void
    {
        $faker = Factory::create();
        $formData = [
            'id' => $faker->uuid,
            'identifier' => $faker->lexify('????????????????????'),
            'timezone' => -28000,
            'version' => '3.2',
            'os' => 'Android Oreo',
            'model' => 'X',
            'type' => 'android',
        ];

        $form = $this->factory->create(DeviceType::class, new Device(new User()));

        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $data = $form->getData();
        $this->assertInstanceOf(Device::class, $data);
        $accessor = new PropertyAccessor();
        foreach ($formData as $key => $value) {
            if ($key === 'type') {
                $this->assertSame(Device::TYPE_ANDROID, $data->getType());
            } else {
                $this->assertSame($value, $accessor->getValue($data, $key));
            }
        }
    }

    /**
     * @param $formData
     * @param $errors
     * @dataProvider getErrors
     */
    public function testSubmitInvalidData($formData, $errors): void
    {
        $this->uniqueEntity->expects($this->never())->method('validate');
        $form = $this->factory->create(DeviceType::class, new Device(new User()));
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertErrors($errors, $form);
    }

    public function getErrors(): array
    {
        return [
            'empty' => [
                [],
                [
                    'id' => ['This value should not be blank.'],
                    'identifier' => ['This value should not be blank.'],
                    'timezone' => ['This value should not be blank.'],
                    'version' => ['This value should not be blank.'],
                    'os' => ['This value should not be blank.'],
                    'model' => ['This value should not be blank.'],
                    'type' => ['This value should not be blank.'],
                ],
            ],
            'invalid' => [
                [
                    'id' => 'xxx',
                    'identifier' => 'id0001',
                    'timezone' => -28000,
                    'version' => '3.2',
                    'os' => 'Android Oreo',
                    'model' => 'X',
                    'type' => 'android',
                ],
                [
                    'id' => ['This is not a valid UUID.'],
                ],
            ],
        ];
    }

    public function testSubmitInvalidUuid(): void
    {
        $callback = function (Device $entity, UniqueEntity $constraint) {
            $this->context->buildViolation($constraint->message)
                ->atPath('id')
                ->setParameter('{{ value }}', $entity->getId())
                ->setInvalidValue($entity->getId())
                ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
                ->addViolation();
        };
        $this->uniqueEntity
            ->expects($this->once())
            ->method('validate')
            ->willReturnCallback($callback->bindTo($this->uniqueEntity, $this->uniqueEntity));
        $form = $this->factory->create(DeviceType::class, new Device(new User()));
        $form->submit([
            'id' => '6ba7b814-9dad-11d1-80b4-00c04fd430c8',
            'identifier' => 'id0001',
            'timezone' => -28000,
            'version' => '3.2',
            'os' => 'Android Oreo',
            'model' => 'X',
            'type' => 'android',
        ]);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertErrors([
            'id' => ['This value is already used.'],
        ], $form);
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
