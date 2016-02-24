<?php

namespace Civix\FrontBundle\Form\Type\Settings;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class Permissions extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                $builder->create(
                    'requiredPermissions',
                    'choice',
                    [
                        'label' => 'Ask for permission',
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
        return 'required_permissions';
    }
}
