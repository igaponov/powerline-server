<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\User;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BaseUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('first_name', Type\TextType::class, [
                'property_path' => 'firstName',
                'description' => 'First name',
            ])
            ->add('last_name', Type\TextType::class, [
                'property_path' => 'lastName',
                'description' => 'Last name',
            ])
            ->add('email', Type\TextType::class, [
                'description' => 'Email',
            ])
            ->add('birth', Type\DateTimeType::class, [
                'widget' => 'single_text',
                'description' => 'Birthday',
            ])
            ->add('address1', Type\TextType::class, [
                'description' => 'Address 1',
            ])
            ->add('address2', Type\TextType::class, [
                'description' => 'Address 2',
            ])
            ->add('city', Type\TextType::class, [
                'description' => 'City',
            ])
            ->add('state', Type\TextType::class, [
                'description' => 'State',
            ])
            ->add('zip', Type\TextType::class, [
                'description' => 'Zip code',
            ])
            ->add('country', Type\TextType::class, [
                'description' => 'Country',
            ])
            ->add('phone', PhoneNumberType::class, [
                'description' => 'Phone number',
            ])
            ->add('avatar_file_name', EncodedFileType::class, [
                'property_path' => 'avatar',
                'description' => 'Avatar, can be an url or a base64-encoded string',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }

}