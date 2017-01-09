<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\KeyToValueTransformer;
use Civix\CoreBundle\Entity\BaseComment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentType extends AbstractType
{
    public function getBlockPrefix()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = BaseComment::getPrivacyLabels();
        $builder
            ->add('comment_body', Type\TextareaType::class, [
                'property_path' => 'commentBody',
            ])
            ->add('privacy', Type\TextType::class, [
                'description' => 'Privacy, one of: '.implode(', ', $choices),
        ]);
        $builder->get('privacy')->addModelTransformer(new KeyToValueTransformer($choices));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }
}