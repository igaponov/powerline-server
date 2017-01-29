<?php

namespace Civix\FrontBundle\Form\Type;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Repository\GroupRepository;
use Mopa\Bundle\BootstrapBundle\Form\Type\FormActionsType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
        $builder->add('officialTitle', null, array('label' => 'Official Title'));
        $builder->add('phone', null, array('label' => 'Phone'));
        $builder->add('privatePhone', null, array('label' => 'Private Phone'));
        $builder->add('city', null, array('label' => 'City'));
        $builder->add('state', EntityType::class, array('class' => 'Civix\CoreBundle\Entity\State', 'choice_label' => 'code'));
        $builder->add('country', ChoiceType::class, array('choices' => array('US' => 'USA')));
        $builder->add('email', null, array('label' => 'Email'));
        $builder->add('privateEmail', null, array('label' => 'Private Email'));
        $builder->add('localGroup', EntityType::class, array(
            'label' => 'Local Group',
            'class' => Group::class,
            'query_builder' => function(GroupRepository $repository) {
                $qb = $repository->createQueryBuilder('g');
                return $qb->where($qb->expr()->in('g.groupType', [
                    Group::GROUP_TYPE_COUNTRY,
                    Group::GROUP_TYPE_STATE,
                    Group::GROUP_TYPE_LOCAL,
                ]));
            },
            'choice_label' => 'officialTitle',
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
            'validation_groups' => array('registration', 'approve'),
        ));
    }
}
