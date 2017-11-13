<?php

namespace Tests\Civix\FrontBundle\Form\Type;

use Civix\FrontBundle\Form\Type\BulkRepresentativeType;
use Civix\FrontBundle\Model\BulkRepresentative;
use Mopa\Bundle\BootstrapBundle\Form\Extension\StaticTextExtension;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\Civix\ApiBundle\FormIntegrationTestCase;

class BulkRepresentativeTypeTest extends FormIntegrationTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'file' => new UploadedFile(__DIR__.'/../../../CoreBundle/data/representatives.csv', 'filename', 'text/csv', 100, null, true),
        ];

        $form = $this->factory->create(BulkRepresentativeType::class);

        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        /** @var BulkRepresentative $data */
        $data = $form->getData();
        $this->assertInstanceOf(BulkRepresentative::class, $data);
        $this->assertSame($formData['file'], $data->getFile());
    }

    /**
     * @param $formData
     * @param $errors
     * @dataProvider getErrors
     */
    public function testSubmitInvalidData($formData, $errors): void
    {
        $form = $this->factory->create(BulkRepresentativeType::class);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertErrors($errors, $form);
    }

    public function getErrors()
    {
        return [
            'empty' => [
                [],
                [
                    'file' => ['This value should not be blank.'],
                ],
            ],
            'invalid' => [
                [
                    'file' => new UploadedFile(__FILE__, 'filename', null, null, null, true),
                ],
                [
                    'file' => ['The mime type of the file is invalid ("text/x-php"). Allowed mime types are "text/csv", "text/plain".'],
                ],
            ],
        ];
    }

    protected function getTypeExtensions(): array
    {
        return array_merge(
            [new StaticTextExtension()]
        );
    }
}
