<?php

namespace Tests\Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\Type\CommentType;
use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Poll;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Tests\Civix\ApiBundle\FormIntegrationTestCase;

class CommentTypeTest extends FormIntegrationTestCase
{
    /**
     * @param BaseComment $comment
     * @dataProvider getComments
     */
    public function testSubmitValidData(BaseComment $comment): void
    {
        $formData = [
            'comment_body' => 'text',
            'privacy' => 'private',
        ];

        $form = $this->factory->create(CommentType::class, $comment);

        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        /** @var BaseComment $data */
        $data = $form->getData();
        $this->assertInstanceOf(BaseComment::class, $data);
        $this->assertSame($formData['comment_body'], $data->getCommentBody());
        $this->assertTrue($data->isPrivate());
    }

    public function getComments()
    {
        return [
            [new Post\Comment(new User())],
            [new Poll\Comment(new User())],
            [new UserPetition\Comment(new User())],
        ];
    }

    /**
     * @param BaseComment $comment
     * @param $formData
     * @param $errors
     * @dataProvider getErrors
     */
    public function testSubmitInvalidData(BaseComment $comment, $formData, $errors): void
    {
        $form = $this->factory->create(CommentType::class, $comment);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertErrors($errors, $form);
    }

    public function getErrors(): array
    {
        $sets = [];
        $comments = $this->getComments();
        $errors = [
            'no data' => [
                [], [
                    'comment_body' => ['This value should not be blank.'],
                ],
            ],
            'empty' => [
                [
                    'comment_body' => '',
                    'privacy' => '',
                ],
                [
                    'comment_body' => ['This value should not be blank.'],
                ],
            ],
            'invalid' => [
                [
                    'comment_body' => str_repeat('x', 501),
                    'privacy' => 'invalid',
                ],
                [
                    'comment_body' => ['This value is too long. It should have 500 characters or less.'],
                    'privacy' => ['This value is not valid.'],
                ]
            ]
        ];
        foreach ($comments as $comment) {
            foreach ($errors as $key => $error) {
                $sets[get_class($comment[0]).' '.$key] = [$comment[0], $error[0], $error[1]];
            }
        }

        return $sets;
    }
}
