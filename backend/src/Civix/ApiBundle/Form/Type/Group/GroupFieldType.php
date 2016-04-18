<?php

namespace Civix\ApiBundle\Form\Type\Group;

use Civix\CoreBundle\Entity\Group\GroupField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class GroupFieldType extends AbstractType
{
    /**
     * Set form fields.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('field_name', null, array(
            'property_path' => 'fieldName',
            'description' => 'Field name',
        ));
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
            'data_class' => GroupField::class,
            'csrf_protection' => false,
        ));
    }
}
