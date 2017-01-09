<?php

namespace Civix\ApiBundle\Form\Type\Group;

use Civix\CoreBundle\Entity\Group;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PermissionSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                $builder->create(
                    'required_permissions',
                    ChoiceType::class,
                    [
                        'property_path' => 'requiredPermissions',
                        'choices' => array_flip(Group::getPermissions()),
                        'multiple' => true,
                        'expanded' => true,
                    ]
                )
            );
    }

    public function getBlockPrefix()
    {
        return '';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Group::class,
            'csrf_protection' => false,
        ]);
    }
}
