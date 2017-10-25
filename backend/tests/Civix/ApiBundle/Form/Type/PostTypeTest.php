<?php

namespace Tests\Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\Type\EncodedFileType;
use Civix\ApiBundle\Form\Type\PostType;
use Civix\Component\ContentConverter\ConverterInterface;
use Civix\CoreBundle\Entity\Post;
use Faker\Factory;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Tests\Civix\ApiBundle\FormIntegrationTestCase;

class PostTypeTest extends FormIntegrationTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $converter;

    public function testSubmitValidData(): void
    {
        $faker = Factory::create();
        $content = base64_encode(file_get_contents(__DIR__.'/../../../../data/image.png'));
        $formData = [
            'body' => $faker->text,
            'automatic_boost' => false,
        ];

        $this->converter->expects($this->once())
            ->method('convert')
            ->with($content)
            ->willReturn(base64_decode($content));
        $form = $this->factory->create(PostType::class);

        $form->submit(array_merge($formData, ['image' => $content]));
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $data = $form->getData();
        $this->assertInstanceOf(Post::class, $data);
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
        $form = $this->factory->create(PostType::class);
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
                    'automatic_boost' => '',
                ],
                [
                    'body' => ['This value should not be blank.'],
                ],
            ],
        ];
    }

    public function testSubmitInvalidImage()
    {
        $formData = [
            'body' => 'test body',
            'image' => base64_encode(file_get_contents(__FILE__)),
        ];
        $this->converter->expects($this->once())
            ->method('convert')
            ->with($formData['image'])
            ->willReturn(base64_decode($formData['image']));
        $form = $this->factory->create(PostType::class);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertErrors([
            'image' => ['This file is not a valid image.'],
        ], $form);
    }

    protected function getExtensions(): array
    {
        $this->converter = $this->createMock(ConverterInterface::class);
        $encodedFileType = new EncodedFileType($this->converter);

        return array_merge(
            [new PreloadedExtension([$encodedFileType], [])],
            parent::getExtensions()
        );
    }
}
