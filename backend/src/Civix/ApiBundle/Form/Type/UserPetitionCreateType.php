<?php
namespace Civix\ApiBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserPetitionCreateType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('is_outsiders_sign', CheckboxType::class, [
                'property_path' => 'outsidersSign',
            ])
            ->add('organization_needed', CheckboxType::class, [
                'property_path' => 'organizationNeeded',
            ])
            // @todo for compatibility with old endpoints
            ->add('link', null, ['mapped' => false])
            ->add('type', null, ['mapped' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => 'create',
        ]);
    }

    public function getParent(): string
    {
        return UserPetitionUpdateType::class;
    }
}