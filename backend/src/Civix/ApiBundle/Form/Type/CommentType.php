<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\KeyToValueTransformer;
use Civix\CoreBundle\Entity\BaseComment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;

class CommentType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = BaseComment::getPrivacyLabels();
        $builder
            ->add('comment_body', Type\TextareaType::class, [
                'empty_data' => '',
                'property_path' => 'commentBody',
            ])
            ->add('privacy', Type\ChoiceType::class, [
                'choices' => $choices,
                'empty_data' => $choices[BaseComment::PRIVACY_PUBLIC],
                'description' => 'Privacy, one of: '.implode(', ', $choices),
        ]);
        $builder->get('privacy')->addModelTransformer(new KeyToValueTransformer($choices));
    }
}