<?php
namespace Civix\ApiBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
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
            ->add('title', Type\TextType::class, [
                'empty_data' => '',
            ])
            ->add('is_outsiders_sign', Type\CheckboxType::class, [
                'property_path' => 'outsidersSign',
            ])
            ->add('organization_needed', Type\CheckboxType::class, [
                'property_path' => 'organizationNeeded',
            ])
            // @todo for compatibility with old endpoints
            ->add('link', null, [
                'mapped' => false,
                'description' => 'For compatibility with old endpoints.',
            ])
            ->add('type', null, [
                'mapped' => false,
                'description' => 'For compatibility with old endpoints.',
            ])
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