<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\KeyToValueTransformer;
use Civix\CoreBundle\Entity\BaseCommentRate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentRateType extends AbstractType
{
    public function getBlockPrefix()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = BaseCommentRate::getRateValueLabels();
        $builder
            ->add('rate_value', TextType::class, [
                'property_path' => 'rateValue',
                'description' => 'Rate, one of: '.implode(', ', $choices),
            ]);
        $builder->get('rate_value')->addModelTransformer(new KeyToValueTransformer($choices));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }
}