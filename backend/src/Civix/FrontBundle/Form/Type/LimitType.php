<?php

namespace Civix\FrontBundle\Form\Type;

use Mopa\Bundle\BootstrapBundle\Form\Type\FormActionsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LimitType extends AbstractType
{
    /**
     * Set form fields.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('questionLimit', IntegerType::class, [
            'label' => 'Limit of question',
        ]);
        $builder->add('buttons', FormActionsType::class, [
            'button_offset' => 'col-sm-offset-3 col-sm-9',
            'buttons' => [
                'submit' => [
                    'type' => SubmitType::class,
                ],
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('label', 'Edit limit of questions');
    }
}
