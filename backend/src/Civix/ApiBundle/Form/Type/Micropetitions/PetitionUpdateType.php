<?php
namespace Civix\ApiBundle\Form\Type\Micropetitions;

use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PetitionUpdateType extends AbstractType
{
    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('petition_body', null, [
                'property_path' => 'petitionBody',
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Petition::class,
            'csrf_protection' => false,
        ]);
    }
}