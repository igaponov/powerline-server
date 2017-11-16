<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\User;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('first_name', TextType::class, [
                'property_path' => 'firstName',
            ])
            ->add('last_name', TextType::class, [
                'property_path' => 'lastName',
            ])
            ->add('username', TextType::class)
            ->add('email', EmailType::class)
            ->add('country', CountryType::class)
            ->add('zip', TextType::class)
            ->add('phone', PhoneNumberType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => 'registration2.2',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}