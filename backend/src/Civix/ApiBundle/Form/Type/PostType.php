<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('body', TextareaType::class, [
                'empty_data' => '',
                'description' => 'Post\'s body',
            ])
            ->add('automatic_boost', CheckboxType::class, [
                'property_path' => 'automaticBoost',
                'description' => 'Post will be boosted automatically',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
            'validation_groups' => 'create',
        ]);
    }
}