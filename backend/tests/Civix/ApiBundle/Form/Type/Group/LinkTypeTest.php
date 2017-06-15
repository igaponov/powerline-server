<?php

namespace Tests\Civix\ApiBundle\Form\Type\Group;

use Civix\ApiBundle\Form\Type\Group\LinkType;
use Civix\CoreBundle\Entity\Group;
use Metadata\PropertyMetadata;
use Tests\Civix\ApiBundle\FormIntegrationTestCase;

class LinkTypeTest extends FormIntegrationTestCase
{
    public function testSubmitValidData(): void
    {
        $group = new Group();
        $group
            ->addLink(new Group\Link('', ''))
            ->addLink(new Group\Link('', ''))
            ->addLink(new Group\Link('', ''))
            ->addLink(new Group\Link('', ''))
        ;

        $formData = [
            'url' => 'example.com',
            'label' => 'test label',
        ];

        $form = $this->factory->create(LinkType::class, null, ['group' => $group]);

        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertInstanceOf(Group\Link::class, $form->getData());
    }

    public function testUpdateManyLinks(): void
    {
        $link = new Group\Link('', '');
        $metadata = new PropertyMetadata(Group\Link::class, 'id');
        $metadata->setValue($link, 1);
        $formData = [
            'url' => 'example.com',
            'label' => 'test label',
        ];
        $group = new Group();
        $group
            ->addLink(new Group\Link('', ''))
            ->addLink(new Group\Link('', ''))
            ->addLink($link)
            ->addLink(new Group\Link('', ''))
            ->addLink(new Group\Link('', ''))
        ;
        $form = $this->factory->create(LinkType::class, $link, ['group' => $group]);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertInstanceOf(Group\Link::class, $form->getData());
    }

    /**
     * @param $formData
     * @param $errors
     * @dataProvider getErrors
     */
    public function testSubmitInvalidData($formData, $errors): void
    {
        $form = $this->factory->create(LinkType::class, null, ['group' => new Group()]);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertErrors($errors, $form);
        $this->assertInstanceOf(Group\Link::class, $form->getData());
    }

    public function getErrors(): array
    {
        return [
            'empty' => [
                [],
                [
                    'url' => ['This value should not be blank.'],
                    'label' => ['This value should not be blank.'],
                ],
            ],
            'invalid' => [
                [
                    'url' => '~/test1',
                    'label' => 'test2',
                ],
                [
                    'url' => ['This value is not a valid URL.'],
                ],
            ],
        ];
    }

    public function testSubmitTooManyLinks(): void
    {
        $formData = [
            'url' => 'example.com',
            'label' => 'test label',
        ];
        $group = new Group();
        $group
            ->addLink(new Group\Link('', ''))
            ->addLink(new Group\Link('', ''))
            ->addLink(new Group\Link('', ''))
            ->addLink(new Group\Link('', ''))
            ->addLink(new Group\Link('', ''))
        ;
        $form = $this->factory->create(LinkType::class, null, ['group' => $group]);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertErrors([null => ['Group should contain 5 links or less.']], $form);
        $this->assertInstanceOf(Group\Link::class, $form->getData());
    }
}
