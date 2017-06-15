<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UserRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', Type\TextType::class, [
                'description' => 'Username',
            ])
            ->add('password', Type\PasswordType::class, [
                'property_path' => 'plainPassword',
                'description' => 'Password',
            ])
            ->add('confirm', Type\TextType::class, [
                'mapped' => false,
                'description' => 'Repeat password',
                'constraints' => [
                    new Callback([
                        'callback' => [$this, 'validatePasswordConfirm'],
                        'groups' => 'registration',
                    ]),
                ],
            ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }

    public function getParent()
    {
        return BaseUserRegistrationType::class;
    }

    public function validatePasswordConfirm($password, ExecutionContextInterface $context)
    {
        $user = $context->getRoot()->getData();
        if (!$user instanceof User) {
            return;
        }

        if ($user->getPlainPassword() !== $password) {
            $context
                ->buildViolation('The password fields must match.')
                ->addViolation()
            ;
        }
    }
}