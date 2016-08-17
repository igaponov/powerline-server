<?php
namespace Civix\ApiBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserPetitionCreateType extends AbstractType
{
    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('is_outsiders_sign', 'checkbox', [
                'property_path' => 'outsidersSign',
            ])
            ->add('organization_needed', 'checkbox', [
                'property_path' => 'organizationNeeded',
            ])
            // @todo for compatibility with old endpoints
            ->add('link', null, ['mapped' => false])
            ->add('type', null, ['mapped' => false])
        ;
    }

    public function getParent()
    {
        return new UserPetitionUpdateType();
    }
}