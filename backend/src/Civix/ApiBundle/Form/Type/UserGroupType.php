<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\KeyToValueTransformer;
use Civix\CoreBundle\Entity\UserGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserGroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = [
            UserGroup::STATUS_ACTIVE => 'active',
            UserGroup::STATUS_BANNED => 'banned',
        ];
        $builder->add('status', TextType::class, [
            'description' => 'User status in the group. One of: '.implode(', ', $choices),
            'empty_data' => 'active',
        ]);
        $builder->get('status')->addModelTransformer(new KeyToValueTransformer($choices));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('csrf_protection', false);
    }

    public function getBlockPrefix()
    {
        return '';
    }
}