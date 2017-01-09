<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\SocialActivity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SocialActivityType extends AbstractType
{
    public function getBlockPrefix()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('ignore', CheckboxType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SocialActivity::class,
            'csrf_protection' => false,
        ]);
    }
}