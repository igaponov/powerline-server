<?php

namespace Civix\ApiBundle\Form\Type\Group;

use Civix\ApiBundle\Form\KeyToValueTransformer;
use Civix\CoreBundle\Entity\Group;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
        $builder->add('membership_control', TextType::class, [
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
    public function getBlockPrefix()
    {
        return '';
    }

    public function configureOptions(OptionsResolver $resolver)
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
