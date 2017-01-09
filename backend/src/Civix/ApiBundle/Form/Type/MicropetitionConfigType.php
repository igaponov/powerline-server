<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\Group;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
        $builder->add('petition_per_month', Type\IntegerType::class, [
            'description' => 'Limit micropetitions per month',
            'property_path' => 'petitionPerMonth',
        ]);
        $builder->add('petition_percent', Type\IntegerType::class, [
            'description' => 'Quorum percentage',
            'property_path' => 'petitionPercent',
        ]);
        $builder->add('petition_duration', Type\IntegerType::class, [
            'description' => 'Quorum duration',
            'property_path' => 'petitionDuration',
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
            'data_class' => Group::class,
            'csrf_protection' => false,
        ));
    }
}
