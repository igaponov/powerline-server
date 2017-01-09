<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\UserGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserGroupPermissionType extends AbstractType
{
    public function getBlockPrefix()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('permissions_name', CheckboxType::class, [
                'property_path' => 'permissionsName',
            ])
            ->add('permissions_contacts', CheckboxType::class, [
                'property_path' => 'permissionsContacts',
            ])
            ->add('permissions_address', CheckboxType::class, [
                'property_path' => 'permissionsAddress',
            ])
            ->add('permissions_city', CheckboxType::class, [
                'property_path' => 'permissionsCity',
            ])
            ->add('permissions_state', CheckboxType::class, [
                'property_path' => 'permissionsState',
            ])
            ->add('permissions_country', CheckboxType::class, [
                'property_path' => 'permissionsCountry',
            ])
            ->add('permissions_zip_code', CheckboxType::class, [
                'property_path' => 'permissionsZipCode',
            ])
            ->add('permissions_email', CheckboxType::class, [
                'property_path' => 'permissionsEmail',
            ])
            ->add('permissions_phone', CheckboxType::class, [
                'property_path' => 'permissionsPhone',
            ])
            ->add('permissions_responses', CheckboxType::class, [
                'property_path' => 'permissionsResponses',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserGroup::class,
            'csrf_protection' => false,
        ]);
    }
}