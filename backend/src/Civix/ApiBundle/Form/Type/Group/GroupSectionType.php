<?php

namespace Civix\ApiBundle\Form\Type\Group;

use Civix\CoreBundle\Entity\GroupSection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class GroupSectionType extends AbstractType
{
    /**
     * Set form fields.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text', array(
            'description' => 'Section title',
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
            'data_class' => GroupSection::class,
            'csrf_protection' => false,
        ));
    }
}
