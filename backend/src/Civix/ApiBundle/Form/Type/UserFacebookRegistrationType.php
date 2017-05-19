<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
            ->add('username', Type\TextType::class, [
                'description' => 'Username',
            ])
            ->add('sex', Type\TextType::class, [
                'description' => 'Sex',
            ]);
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var User $data */
            $data = $event->getData();
            $data->setPlainPassword(random_bytes(20));
        }, 900);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault(
            'validation_groups',
            [
                'registration',
                'facebook',
            ]
        );
    }

    public function getBlockPrefix()
    {
        return '';
    }

    public function getParent()
    {
        return BaseUserRegistrationType::class;
    }
}