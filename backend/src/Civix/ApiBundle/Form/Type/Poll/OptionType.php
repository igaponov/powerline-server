<?php

namespace Civix\ApiBundle\Form\Type\Poll;

use Civix\CoreBundle\Entity\Poll\Option;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
        $builder->add('value', Type\TextType::class)
            ->add('payment_amount', Type\IntegerType::class, [
                'property_path' => 'paymentAmount',
                'required' => false,
            ])
            ->add('is_user_amount', Type\CheckboxType::class, [
                'property_path' => 'isUserAmount',
                'required' => false,
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
        $resolver->setDefaults([
            'data_class' => Option::class,
            'csrf_protection' => false,
        ]);
    }
}
