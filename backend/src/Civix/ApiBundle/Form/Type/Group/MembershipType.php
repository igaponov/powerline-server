<?php

namespace Civix\ApiBundle\Form\Type\Group;

use Civix\ApiBundle\Form\KeyToValueTransformer;
use Civix\CoreBundle\Entity\Group;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MembershipType extends AbstractType
{
    /**
     * Set form fields.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = Group::getMembershipControlChoices();
        $builder->add('membership_control', 'text', [
            'property_path' => 'membershipControl',
            'description' => 'Membership Control, on of: '.implode(', ', $choices),
        ]);
        $builder->add('membership_passcode', null, [
            'property_path' => 'membershipPasscode',
            'description' => 'Passcode',
        ]);

        $builder->get('membership_control')
            ->addModelTransformer(new KeyToValueTransformer($choices));
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
            'validation_groups' => function (Form $form) {
                $groups = ['membership-control'];
                /** @var Group $data */
                $data = $form->getData();
                if ($data->getMembershipControl() == Group::GROUP_MEMBERSHIP_PASSCODE) {
                    $groups[] = 'membership-control-passcode';
                }
                
                return $groups;
            }
        ));
    }
}
