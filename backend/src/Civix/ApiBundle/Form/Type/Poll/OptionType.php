<?php

namespace Civix\ApiBundle\Form\Type\Poll;

use Civix\CoreBundle\Entity\Poll\Option;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Option form type.
 */
class OptionType extends AbstractType
{
    /**
     * Set form fields.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('value', 'text')
            ->add('payment_amount', 'integer', [
                'property_path' => 'paymentAmount',
                'required' => false,
            ])
            ->add('is_user_amount', 'checkbox', [
                'property_path' => 'isUserAmount',
                'required' => false,
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
        $resolver->setDefaults([
            'data_class' => Option::class,
            'csrf_protection' => false,
        ]);
    }
}
