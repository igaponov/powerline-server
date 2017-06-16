<?php

namespace Tests\Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Entity\ActivityData;
use Civix\ApiBundle\Form\Type\ActivitiesType;
use Tests\Civix\ApiBundle\FormIntegrationTestCase;

class ActivitiesTypeTest extends FormIntegrationTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'activities' => [
                ['id' => 1, 'read' => true],
                ['id' => 2, 'read' => true],
            ],
        ];

        $form = $this->factory->create(ActivitiesType::class);

        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $data = $form->getData();
        $this->assertArrayHasKey('activities', $data);
        /** @var ActivityData[] $activities */
        $activities = $data['activities'];
        $this->assertCount(2, $activities);
        foreach ($activities as $k => $activity) {
            $this->assertInstanceOf(ActivityData::class, $activity);
            $this->assertSame($formData['activities'][$k]['id'], $activity->getId());
            $this->assertSame($formData['activities'][$k]['read'], $activity->getRead());
        }
    }

    /**
     * @param $formData
     * @param $errors
     * @dataProvider getErrors
     */
    public function testSubmitInvalidData($formData, $errors): void
    {
        $form = $this->factory->create(ActivitiesType::class);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertErrors($errors, $form);
    }

    public function getErrors(): array
    {
        return [
            'empty' => [
                ['activities' => []],
                [
                    'This value should not be blank.',
                    'activities' => [],
                ],
            ],
            'invalid' => [
                ['activities' => [['id' => '']]],
                [
                    'activities' => [
                        [
                            'id' => ['This value should not be blank.'],
                            'read' => ['This value should not be blank.'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
