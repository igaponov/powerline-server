<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\KeyToValueTransformer;
use Civix\CoreBundle\Entity\Notification\Device;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeviceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = Device::getTypeLabels();
        $builder
            ->add('id', Type\TextType::class)
            ->add('identifier', Type\TextType::class)
            ->add('timezone', Type\IntegerType::class)
            ->add('version', Type\TextType::class)
            ->add('os', Type\TextType::class)
            ->add('model', Type\TextType::class)
            ->add('type', Type\ChoiceType::class, [
                'choices' => $choices,
                'description' => 'Device type, one of: '.implode(', ', $choices),
            ]);
        $builder->get('type')->addModelTransformer(new KeyToValueTransformer($choices));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', Device::class);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}