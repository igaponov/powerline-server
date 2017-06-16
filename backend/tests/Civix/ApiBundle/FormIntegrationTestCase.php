<?php

namespace Tests\Civix\ApiBundle;

use Nelmio\ApiDocBundle\Form\Extension\DescriptionFormTypeExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class FormIntegrationTestCase extends TestCase
{
    /**
     * @var FormFactoryInterface
     */
    protected $factory;

    public function assertErrors(array $expectedErrors, FormInterface $form): void
    {
        $errors = $form->getErrors();
        $expectedMessages = array_filter($expectedErrors, function ($message, $key) {
            return is_string($message) && is_int($key);
        }, ARRAY_FILTER_USE_BOTH);
        $this->assertCount($errors->count(), $expectedMessages);
        if ($expectedMessages) {
            $this->assertFalse($form->isValid(), 'Form must be invalid');
            $messages = array_map(
                function (FormError $error) {
                    return $error->getMessage();
                },
                iterator_to_array($errors)
            );
            sort($messages);
            sort($expectedMessages);
            $this->assertSame($expectedMessages, $messages, (string)$errors);
        }
        foreach ($form as $name => $element) {
            $this->assertErrors($expectedErrors[$name] ?? [], $element);
        }
    }

    protected function setUp(): void
    {
        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->addTypeExtensions($this->getTypeExtensions())
            ->getFormFactory();
    }

    protected function tearDown(): void
    {
        $this->factory = null;
    }

    protected function getValidator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();
    }

    protected function getExtensions(): array
    {
        return [new ValidatorExtension($this->getValidator())];
    }

    protected function getTypeExtensions(): array
    {
        return [new DescriptionFormTypeExtension()];
    }
}
