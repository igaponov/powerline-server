<?php

namespace Tests\Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\Type\CommentRateType;
use Civix\CoreBundle\Entity\BaseCommentRate;
use Civix\CoreBundle\Entity\Poll;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserPetition;
use Tests\Civix\ApiBundle\FormIntegrationTestCase;

class CommentRateTypeTest extends FormIntegrationTestCase
{
    /**
     * @param BaseCommentRate $rate
     * @param string $value
     * @dataProvider getRates
     */
    public function testSubmitValidData(BaseCommentRate $rate, string $value): void
    {
        $formData = [
            'rate_value' => $value,
        ];

        $form = $this->factory->create(CommentRateType::class, $rate);

        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        /** @var BaseCommentRate $data */
        $data = $form->getData();
        $this->assertInstanceOf(BaseCommentRate::class, $data);
        $this->assertSame($formData['rate_value'], $data->getRateValueLabel());
    }

    public function getCommentRates()
    {
        return [
            [new Post\CommentRate()],
            [new Poll\CommentRate()],
            [new UserPetition\CommentRate()],
        ];
    }

    public function getRates()
    {
        $sets = [];
        $rates = $this->getCommentRates();
        $values = ['up', 'down', 'delete'];
        foreach ($rates as $rate) {
            foreach ($values as $key => $value) {
                $sets[get_class($rate[0]).' '.$key] = [$rate[0], $value];
            }
        }

        return $sets;
    }

    /**
     * @param BaseCommentRate $rate
     * @param $formData
     * @param $errors
     * @dataProvider getErrors
     */
    public function testSubmitInvalidData(BaseCommentRate $rate, $formData, $errors): void
    {
        $form = $this->factory->create(CommentRateType::class, $rate);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertErrors($errors, $form);
    }

    public function getErrors(): array
    {
        $sets = [];
        $rates = $this->getCommentRates();
        $errors = [
            'no data' => [
                [], ['rate_value' => ['This value should not be blank.']],
            ],
            'empty' => [
                [
                    'rate_value' => '',
                ],
                [
                    'rate_value' => ['This value should not be blank.'],
                ],
            ],
            'invalid' => [
                [
                    'rate_value' => 'invalid',
                ],
                [
                    'rate_value' => ['This value is not valid.'],
                ]
            ]
        ];
        foreach ($rates as $rate) {
            foreach ($errors as $key => $error) {
                $sets[get_class($rate[0]).' '.$key] = [$rate[0], $error[0], $error[1]];
            }
        }

        return $sets;
    }
}
