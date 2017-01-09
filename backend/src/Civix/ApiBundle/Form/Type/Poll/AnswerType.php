<?php
namespace Civix\ApiBundle\Form\Type\Poll;

use Civix\ApiBundle\Form\KeyToValueTransformer;
use Civix\CoreBundle\Entity\Poll\Answer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnswerType extends AbstractType
{
    public function getBlockPrefix()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = Answer::getPrivacyLabels();
        $builder
            ->add('comment', Type\TextareaType::class, [
                'required' => false,
            ])
            ->add('privacy', Type\TextType::class, [
                'description' => 'Privacy, one of: '.implode(', ', $choices),
                'required' => false,
            ])
            ->add('payment_amount', Type\NumberType::class, [
                'property_path' => 'paymentAmount',
                'required' => false,
            ]);
        $builder->get('privacy')->addModelTransformer(new KeyToValueTransformer($choices));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Answer::class,
            'csrf_protection' => false,
        ]);
    }
}