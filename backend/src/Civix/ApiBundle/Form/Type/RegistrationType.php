<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Validator\Constraints\AuthyCodeProperty;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

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
            ->add('country', CountryType::class, [
                'description' => 'ISO 3166-1 alpha-2 code'
            ])
            ->add('zip', TextType::class)
            ->add('phone', PhoneNumberType::class, [
                'description' => 'Phone in E.164 format.',
            ])
            ->add('code', TextType::class, [
                'mapped' => false,
                'description' => 'Verification code.',
            ])
        ;
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $form->add('code', TextType::class, [
                    'mapped' => false,
                    'validation_groups' => ['registration2.2', 'authy'],
                    'constraints' => [
                        new NotBlank(['groups' => ['registration2.2']]),
                        new AuthyCodeProperty([
                            'phoneValue' => function() use ($form) {
                                return $form->get('phone')->getData();
                            },
                            'groups' => ['authy'],
                        ]),
                    ]
                ]);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}