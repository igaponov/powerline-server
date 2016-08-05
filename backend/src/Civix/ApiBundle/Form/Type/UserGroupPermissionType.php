<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\UserGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserGroupPermissionType extends AbstractType
{
    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('permissions_name', 'checkbox', [
                'property_path' => 'permissionsName',
            ])
            ->add('permissions_contacts', 'checkbox', [
                'property_path' => 'permissionsContacts',
            ])
            ->add('permissions_address', 'checkbox', [
                'property_path' => 'permissionsAddress',
            ])
            ->add('permissions_city', 'checkbox', [
                'property_path' => 'permissionsCity',
            ])
            ->add('permissions_state', 'checkbox', [
                'property_path' => 'permissionsState',
            ])
            ->add('permissions_country', 'checkbox', [
                'property_path' => 'permissionsCountry',
            ])
            ->add('permissions_zip_code', 'checkbox', [
                'property_path' => 'permissionsZipCode',
            ])
            ->add('permissions_email', 'checkbox', [
                'property_path' => 'permissionsEmail',
            ])
            ->add('permissions_phone', 'checkbox', [
                'property_path' => 'permissionsPhone',
            ])
            ->add('permissions_responses', 'checkbox', [
                'property_path' => 'permissionsResponses',
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserGroup::class,
            'csrf_protection' => false,
        ]);
    }
}