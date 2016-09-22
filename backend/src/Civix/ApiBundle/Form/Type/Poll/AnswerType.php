<?php
namespace Civix\ApiBundle\Form\Type\Poll;

use Civix\ApiBundle\Form\KeyToValueTransformer;
use Civix\CoreBundle\Entity\Poll\Answer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AnswerType extends AbstractType
{
    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = Answer::getPrivacyLabels();
        $builder
            ->add('comment', 'textarea', [
                'required' => false,
            ])
            ->add('privacy', 'text', [
                'description' => 'Privacy, one of: '.implode(', ', $choices),
                'required' => false,
            ])
            ->add('payment_amount', 'number', [
                'required' => false,
            ]);
        $builder->get('privacy')->addModelTransformer(new KeyToValueTransformer($choices));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Answer::class,
            'csrf_protection' => false,
        ]);
    }
}