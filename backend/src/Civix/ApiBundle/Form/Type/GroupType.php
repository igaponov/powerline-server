<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\Group;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class GroupType extends AbstractType
{
    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('username', 'text', [
            'description' => 'User name',
        ]);
        $builder->add('plain_password', 'password', [
            'property_path' => 'plainPassword',
            'description' => 'User password',
        ]);
        $builder->add('manager_first_name', 'text', ['property_path' => 'managerFirstName']);
        $builder->add('manager_last_name', 'text', ['property_path' => 'managerLastName']);
        $builder->add('manager_email', 'text', ['property_path' => 'managerEmail']);
        $builder->add('manager_phone', 'text', ['property_path' => 'managerPhone']);
        $builder->add('official_type', 'text', [
            'property_path' => 'officialType',
            'description' => 'Official type, can be one of '.join(', ', Group::getOfficialTypes()),
        ]);
        $builder->add('official_name', 'text', ['property_path' => 'officialName']);
        $builder->add('official_description', 'text', ['property_path' => 'officialDescription']);
        $builder->add('acronym', null, ['max_length' => 4]);
        $builder->add('official_address', 'text', ['property_path' => 'officialAddress']);
        $builder->add('official_city', 'text', ['property_path' => 'officialCity']);
        $builder->add('official_state', 'text', ['property_path' => 'officialState']);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Group::class,
            'csrf_protection' => false,
        ));
    }
}