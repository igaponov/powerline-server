<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class BaseUserRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email_confirm', EmailType::class, [
                'mapped' => false,
                'description' => 'Repeat email',
                'constraints' => [
                    new Callback([
                        'callback' => [$this, 'validateEmailConfirm'],
                        'groups' => 'registration',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => [
                'registration',
            ],
        ]);
    }

    public function getParent()
    {
        return BaseUserType::class;
    }

    public function validateEmailConfirm($email, ExecutionContextInterface $context)
    {
        $user = $context->getRoot()->getData();
        if (!$user instanceof User) {
            return;
        }

        if ($user->getEmail() !== $email) {
            $context
                ->buildViolation('The email fields must match.')
                ->addViolation()
            ;
        }
    }
}