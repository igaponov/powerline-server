<?php

namespace Civix\ApiBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFacebookRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('facebook_id', Type\TextType::class, [
                'property_path' => 'facebookId',
                'description' => 'Facebook ID',
            ])
            ->add('facebook_token', Type\TextType::class, [
                'property_path' => 'facebookToken',
                'description' => 'Facebook Token',
            ])
            ->add('facebook_link', Type\TextType::class, [
                'property_path' => 'facebookLink',
                'description' => 'Facebook Link',
            ])
            ->add('sex', Type\TextType::class, [
                'description' => 'Sex',
            ]);
        $builder->get('password')->setEmptyData(random_bytes(20));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('validation_groups', [
            'registration',
            'facebook',
        ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }

    public function getParent()
    {
        return UserRegistrationType::class;
    }
}