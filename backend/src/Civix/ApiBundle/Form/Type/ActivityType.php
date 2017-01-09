<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Entity\ActivityData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityType extends AbstractType
{
    public function getBlockPrefix()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', Type\IntegerType::class, [
                'description' => 'Activity id',
                'required' => true,
            ])
            ->add('read', Type\CheckboxType::class, [
                'description' => 'Mark activity as read',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ActivityData::class,
            'csrf_protection' => false,
        ]);
    }
}