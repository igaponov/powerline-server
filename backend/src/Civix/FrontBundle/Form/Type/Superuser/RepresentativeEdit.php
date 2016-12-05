<?php

namespace Civix\FrontBundle\Form\Type\Superuser;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Repository\GroupRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Representative registration form.
 */
class RepresentativeEdit extends AbstractType
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
        $builder->add('state', 'entity', array('class' => 'Civix\CoreBundle\Entity\State', 'property' => 'code'));
        $builder->add('country', 'choice', array('choices' => array('US' => 'USA')));
        $builder->add('email', null, array('label' => 'Email'));
        $builder->add('privateEmail', null, array('label' => 'Private Email'));
        $builder->add('localGroup', 'entity', array(
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
            'property' => 'officialTitle',
        ));
    }

    /**
     * Get unique name for form.
     *
     * @return string
     */
    public function getName()
    {
        return 'representative_edit';
    }

    /**
     * Set default form option.
     *
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'validation_groups' => array('registration', 'approve'),
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
        ));
    }
}
