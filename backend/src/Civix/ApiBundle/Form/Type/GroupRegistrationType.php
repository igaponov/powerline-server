<?php
namespace Civix\ApiBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class GroupRegistrationType extends AbstractType
{
    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('plain_password', 'password', [
            'property_path' => 'plainPassword',
            'description' => 'User password',
        ]);
    }

    public function getParent()
    {
        return new GroupType();
    }
}