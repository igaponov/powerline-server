<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Model\Group\Worksheet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorksheetType extends AbstractType
{
    public function getBlockPrefix()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('passcode', Type\TextType::class)
            ->add('answered_fields', Type\CollectionType::class, [
                'property_path' => 'answeredFields',
                'entry_type' => WorksheetFieldType::class,
                'allow_add' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Worksheet::class,
            'csrf_protection' => false,
            'validation_groups' => 'group-join',
        ]);
    }
}