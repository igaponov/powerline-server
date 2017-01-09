<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\Stripe\Card;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CardType extends AbstractType
{
    public function getBlockPrefix()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('source', TextType::class, [
                'description' => 'Token, returned by Stripe.js.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Card::class,
            'csrf_protection' =>false,
        ]);
    }
}