<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Model\Group\Worksheet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class WorksheetType extends AbstractType
{
    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('passcode', 'text')
            ->add('answered_fields', 'collection', [
                'property_path' => 'answeredFields',
                'type' => new WorksheetFieldType(),
                'allow_add' => true,
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Worksheet::class,
            'csrf_protection' => false,
            'validation_groups' => 'group-join',
        ]);
    }
}