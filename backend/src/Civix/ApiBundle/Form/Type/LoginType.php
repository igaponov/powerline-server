<?php

namespace Civix\ApiBundle\Form\Type;

use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('phone', PhoneNumberType::class);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}