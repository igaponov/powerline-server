<?php

namespace Civix\ApiBundle\Form\Type\Group;

use Civix\CoreBundle\Entity\Group\AdvancedAttributes;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdvancedAttributesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('welcome_message', Type\TextareaType::class, [
                'property_path' => 'welcomeMessage',
                'description' => 'Welcome message',
                'required' => false,
            ])
            ->add('welcome_video', Type\UrlType::class, [
                'property_path' => 'welcomeVideo',
                'description' => 'Welcome video link',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AdvancedAttributes::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }
}