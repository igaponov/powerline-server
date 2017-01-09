<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\Group;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupType extends AbstractType
{
    public function getBlockPrefix()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('manager_first_name', Type\TextType::class, [
            'property_path' => 'managerFirstName',
            'required' => false,
        ]);
        $builder->add('manager_last_name', Type\TextType::class, [
            'property_path' => 'managerLastName',
            'required' => false,
        ]);
        $builder->add('manager_email', Type\TextType::class, [
            'property_path' => 'managerEmail',
            'required' => false,
        ]);
        $builder->add('manager_phone', Type\TextType::class, [
            'property_path' => 'managerPhone',
            'required' => false,
        ]);
        $builder->add('official_type', Type\TextType::class, [
            'property_path' => 'officialType',
            'description' => 'Official type, can be one of '.join(', ', Group::getOfficialTypes()),
        ]);
        $builder->add('official_name', Type\TextType::class, [
            'property_path' => 'officialName',
        ]);
        $builder->add('official_description', Type\TextType::class, [
            'property_path' => 'officialDescription',
            'required' => false,
        ]);
        $builder->add('acronym', Type\TextType::class, [
            'required' => false,
        ]);
        $builder->add('official_address', Type\TextType::class, [
            'property_path' => 'officialAddress',
            'required' => false,
        ]);
        $builder->add('official_city', Type\TextType::class, [
            'property_path' => 'officialCity',
            'required' => false,
        ]);
        $builder->add('official_state', Type\TextType::class, [
            'property_path' => 'officialState',
            'required' => false,
        ]);
        $builder->add('transparency', Type\TextType::class, [
            'required' => false,
            'description' => 'Transparency, can be one of '.join(', ', Group::getTransparencyStates()),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Group::class,
            'csrf_protection' => false,
        ));
    }
}