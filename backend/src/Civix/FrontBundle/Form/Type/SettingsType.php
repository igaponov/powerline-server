<?php

namespace Civix\FrontBundle\Form\Type;

use Civix\FrontBundle\Form\Model\CoreSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach (CoreSettings::$fields as $key => $params) {
            $builder->add($key, IntegerType::class, ['label' => $params[1]]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CoreSettings::class,
        ]);
    }
}
