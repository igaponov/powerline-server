<?php
namespace Civix\ApiBundle\Form\Type\Micropetitions;

use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PetitionCreateType extends AbstractType
{
    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = Petition::getTypes();
        $builder
            ->add('title')
            ->add('link', null, [
                'required' => false,
            ])
            ->add('is_outsiders_sign', 'checkbox', [
                'property_path' => 'isOutsidersSign',
            ])
            ->add('type', 'text', [
                'description' => 'Petition type, can be one of: '.implode(', ', $choices)
            ])
        ;
    }

    public function getParent()
    {
        return new PetitionUpdateType();
    }
}