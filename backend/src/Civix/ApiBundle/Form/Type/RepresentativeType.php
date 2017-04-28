<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\Representative;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Representative registration form.
 */
class RepresentativeType extends AbstractType
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
        ]);
        $builder->add('city', null, [
            'description' => 'City',
        ]);
        $builder->add('state', EntityType::class, [
            'class' => 'Civix\CoreBundle\Entity\State',
            'choice_label' => 'code',
        ]);
        $builder->add('country', Type\ChoiceType::class, [
            'choices' => ['USA' => 'US'],
        ]);
        $builder->add('phone', null, [
            'description' => 'Public Phone',
        ]);
        $builder->add('private_phone', null, [
            'property_path' => 'privatePhone',
            'description' => 'Private Phone',
        ]);
        $builder->add('email', null, [
            'description' => 'Email',
        ]);
        $builder->add('private_email', null, [
            'property_path' => 'privateEmail',
            'description' => 'Private Email',
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
            'data_class' => Representative::class,
            'validation_groups' => ['registration', 'avatar'],
            'csrf_protection' => false,
        ));
    }
}
