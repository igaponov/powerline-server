<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\UserFollow;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFollowType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('notifying', CheckboxType::class, [
                'description' => 'Get notifications from follower',
            ])
            ->add('do_not_disturb_till', DateTimeType::class, [
                'widget' => 'single_text',
                'description' => 'Mute notification from follower till X',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', UserFollow::class);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}