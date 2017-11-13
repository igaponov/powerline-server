<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\KeyToValueTransformer;
use Civix\CoreBundle\Entity\BaseCommentRate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentRateType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = BaseCommentRate::getRateValueLabels();
        $builder
            ->add('rate_value', ChoiceType::class, [
                'property_path' => 'rateValue',
                'choices' => $choices,
                'description' => 'Rate, one of: '.implode(', ', $choices),
            ]);
        $builder->get('rate_value')->addModelTransformer(new KeyToValueTransformer($choices));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BaseCommentRate::class,
        ]);
    }
}