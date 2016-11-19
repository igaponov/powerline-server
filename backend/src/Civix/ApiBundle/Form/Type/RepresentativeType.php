<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\Representative;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
        $builder->add('state', 'entity', [
            'class' => 'Civix\CoreBundle\Entity\State',
            'property' => 'code',
        ]);
        $builder->add('country', 'choice', [
            'choices' => ['US' => 'USA'],
        ]);
        $builder->add('phone', null, [
            'description' => 'Public Phone',
        ]);
        $builder->add('private_phone', null, [
            'description' => 'Private Phone',
        ]);
        $builder->add('email', null, [
            'description' => 'Email',
        ]);
        $builder->add('private_email', null, [
            'description' => 'Private Email',
        ]);
    }

    /**
     * Get unique name for form.
     *
     * @return string
     */
    public function getName()
    {
        return '';
    }

    /**
     * Set default form option.
     *
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Representative::class,
            'validation_groups' => ['registration'],
            'csrf_protection' => false,
        ));
    }
}
