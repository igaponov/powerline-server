<?php

namespace Tests\Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\Type\PostType;
use Civix\CoreBundle\Entity\Post;
use Faker\Factory;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Tests\Civix\ApiBundle\FormIntegrationTestCase;

class PostTypeTest extends FormIntegrationTestCase
{
    public function testSubmitValidData(): void
    {
        $faker = Factory::create();
        $formData = [
            'body' => $faker->text,
            'automatic_boost' => false,
        ];

        $form = $this->factory->create(PostType::class);

        $form->submit($formData);
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
}
