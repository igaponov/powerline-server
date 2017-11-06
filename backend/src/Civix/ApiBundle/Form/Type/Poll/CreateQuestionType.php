<?php

namespace Civix\ApiBundle\Form\Type\Poll;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;

class CreateQuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('options', CollectionType::class, [
            'allow_add' => true,
            'delete_empty' => true,
            'entry_type' => OptionType::class,
        ]);
    }

    public function getParent(): string
    {
        return QuestionType::class;
    }
}