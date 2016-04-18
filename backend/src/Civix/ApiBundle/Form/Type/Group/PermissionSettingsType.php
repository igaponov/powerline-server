<?php

namespace Civix\ApiBundle\Form\Type\Group;

use Civix\CoreBundle\Entity\Group;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PermissionSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                $builder->create(
                    'required_permissions',
                    'choice',
                    [
                        'property_path' => 'requiredPermissions',
                        'choices' => [
                            'permissions_name' => 'Name',
                            'permissions_address' => 'Street Address',
                            'permissions_city' => 'City',
                            'permissions_state' => 'State',
                            'permissions_country' => 'Country',
                            'permissions_zip_code' => 'Zip Code',
                            'permissions_email' => 'Email',
                            'permissions_phone' => 'Phone Number',
                            'permissions_responses' => 'Responses',
                        ],
                        'multiple' => true,
                        'expanded' => true,
                    ]
                )
            );
    }

    public function getName()
    {
        return '';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Group::class,
            'csrf_protection' => false,
        ]);
    }
}
