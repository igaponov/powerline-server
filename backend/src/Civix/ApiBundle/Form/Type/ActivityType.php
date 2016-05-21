<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Entity\ActivityData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ActivityType extends AbstractType
{
    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', 'integer', [
                'description' => 'Activity id',
                'required' => true,
            ])
            ->add('read', 'checkbox', [
                'description' => 'Mark activity as read',
                'required' => true,
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ActivityData::class,
            'csrf_protection' => false,
        ]);
    }
}