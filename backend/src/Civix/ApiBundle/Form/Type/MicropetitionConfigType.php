<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\Group;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MicropetitionConfigType extends AbstractType
{
    /**
     * Set form fields.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('petition_per_month', 'integer', [
            'description' => 'Limit micropetitions per month',
            'property_path' => 'petitionPerMonth',
        ]);
        $builder->add('petition_percent', 'integer', [
            'description' => 'Quorum percentage',
            'property_path' => 'petitionPercent',
        ]);
        $builder->add('petition_duration', 'integer', [
            'description' => 'Quorum duration',
            'property_path' => 'petitionDuration',
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
            'data_class' => Group::class,
            'csrf_protection' => false,
        ));
    }
}
