<?php

namespace Tests\Civix\ApiBundle\Form\Type\Group;

use Civix\ApiBundle\Form\Type\Group\TagType;
use Civix\CoreBundle\Entity\Group;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Tests\Civix\ApiBundle\FormIntegrationTestCase;

/**
 * @property ExecutionContextInterface $context
 */
class TagTypeTest extends FormIntegrationTestCase
{
    /**
     * @var UniqueEntityValidator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $uniqueEntity;

    public function testSubmitValidData(): void
    {
        $formData = [
            'name' => 'test tag',
        ];

        $form = $this->factory->create(TagType::class);

        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertInstanceOf(Group\Tag::class, $form->getData());
        $this->assertSame($formData['name'], $form->getData()->getName());
    }

    public function testSubmitEmptyData(): void
    {
        $form = $this->factory->create(TagType::class);
        $form->submit([]);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertErrors(['name' => ['This value should not be blank.']], $form);
        $this->assertInstanceOf(Group\Tag::class, $form->getData());
    }

    public function testSubmitInvalidData(): void
    {
        $callback = function (Group\Tag $entity, UniqueEntity $constraint) {
            $this->context->buildViolation($constraint->message)
                ->atPath('name')
                ->setParameter('{{ value }}', $entity->getName())
                ->setInvalidValue($entity->getName())
                ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
                ->addViolation();
        };
        $this->uniqueEntity
            ->expects($this->once())
            ->method('validate')
            ->willReturnCallback($callback->bindTo($this->uniqueEntity, $this->uniqueEntity));
        $form = $this->factory->create(TagType::class);
        $form->submit(['name' => 'duplicate']);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertErrors(['name' => ['This value is already used.']], $form);
        $this->assertInstanceOf(Group\Tag::class, $form->getData());
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
