<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\Stripe\Card;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CardType extends AbstractType
{
    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('source', 'text', [
                'description' => 'Token, returned by Stripe.js.',
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Card::class,
            'csrf_protection' =>false,
        ]);
    }
}