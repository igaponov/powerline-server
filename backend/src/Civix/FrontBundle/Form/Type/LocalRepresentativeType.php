<?php

namespace Civix\FrontBundle\Form\Type;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Repository\RepresentativeRepository;
use Mopa\Bundle\BootstrapBundle\Form\Type\FormActionsType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalRepresentativeType extends AbstractType
{
    /**
     * Set form fields.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('localRepresentatives', EntityType::class, array(
            'class' => 'CivixCoreBundle:Representative',
            'label' => 'Local representatives',
            'attr' => array('class' => 'span6'),
            'by_reference' => false,
            'multiple' => true,
            'required' => false,
            'query_builder' => function (RepresentativeRepository $er) {
                return $er->getQueryBuilderLocalRepr();
            },
        ));
        $builder->add('buttons', FormActionsType::class, [
            'button_offset' => 'col-sm-offset-3 col-sm-9',
            'buttons' => [
                'submit' => [
                    'type' => SubmitType::class,
                ],
            ]
        ]);
    }

    /**
     * Set default form option.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Group::class,
        ));
    }
}
