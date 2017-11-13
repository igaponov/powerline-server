<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\UserRepresentative;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Civix\CoreBundle\Entity\State;

/**
 * Representative registration form.
 */
class UserRepresentativeType extends AbstractType
{
    /**
     * Set form fields.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('official_title', null, [
            'property_path' => 'officialTitle',
            'description' => 'Official Title',
            'empty_data' => '',
        ]);
        $builder->add('city', null, [
            'description' => 'City',
            'empty_data' => '',
        ]);
        $builder->add('state', EntityType::class, [
            'class' => State::class,
            'choice_label' => 'code',
        ]);
        $builder->add('country', Type\ChoiceType::class, [
            'choices' => ['USA' => 'US'],
            'empty_data' => '',
        ]);
        $builder->add('phone', null, [
            'description' => 'Public Phone',
            'empty_data' => '',
        ]);
        $builder->add('private_phone', null, [
            'property_path' => 'privatePhone',
            'description' => 'Private Phone',
            'empty_data' => '',
        ]);
        $builder->add('email', null, [
            'description' => 'Email',
            'empty_data' => '',
        ]);
        $builder->add('private_email', null, [
            'property_path' => 'privateEmail',
            'description' => 'Private Email',
            'empty_data' => '',
        ]);
        $builder->add('avatar', EncodedFileType::class, [
            'required' => false,
            'description' => 'Base64-encoded content',
        ]);
    }

    /**
     * Get unique name for form.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return '';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => UserRepresentative::class,
            'validation_groups' => ['registration', 'avatar'],
            'csrf_protection' => false,
        ));
    }
}
