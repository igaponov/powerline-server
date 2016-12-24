<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\DataTransformer\Base64EncodedStringToUploadedFileTransformer;
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
        $builder->add('manager_first_name', 'text', [
            'property_path' => 'managerFirstName',
            'required' => false,
        ]);
        $builder->add('manager_last_name', 'text', [
            'property_path' => 'managerLastName',
            'required' => false,
        ]);
        $builder->add('manager_email', 'text', [
            'property_path' => 'managerEmail',
            'required' => false,
        ]);
        $builder->add('manager_phone', 'text', [
            'property_path' => 'managerPhone',
            'required' => false,
        ]);
        $builder->add('official_type', 'text', [
            'property_path' => 'officialType',
            'description' => 'Official type, can be one of '.join(', ', Group::getOfficialTypes()),
        ]);
        $builder->add('official_name', 'text', [
            'property_path' => 'officialName',
        ]);
        $builder->add('official_description', 'text', [
            'property_path' => 'officialDescription',
            'required' => false,
        ]);
        $builder->add('acronym', null, [
            'max_length' => 4,
            'required' => false,
        ]);
        $builder->add('official_address', 'text', [
            'property_path' => 'officialAddress',
            'required' => false,
        ]);
        $builder->add('official_city', 'text', [
            'property_path' => 'officialCity',
            'required' => false,
        ]);
        $builder->add('official_state', 'text', [
            'property_path' => 'officialState',
            'required' => false,
        ]);
        $builder->add('transparency', 'text', [
            'required' => false,
            'description' => 'Transparency, can be one of '.join(', ', Group::getTransparencyStates()),
        ]);
        $builder->add('avatar', 'textarea', [
            'required' => false,
            'description' => 'Base64-encoded content',
        ]);
        $transformer = new Base64EncodedStringToUploadedFileTransformer();
        $builder->get('avatar')->addModelTransformer($transformer);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Group::class,
            'csrf_protection' => false,
        ));
    }
}