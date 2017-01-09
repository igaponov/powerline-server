<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Model\Group\WorksheetField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorksheetFieldType extends AbstractType
{
    public function getBlockPrefix()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', Type\IntegerType::class)
            ->add('value', Type\TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => WorksheetField::class,
            'csrf_protection' => false,
        ]);
    }
}