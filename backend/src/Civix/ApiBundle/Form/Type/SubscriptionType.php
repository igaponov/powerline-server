<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\KeyToValueTransformer;
use Civix\CoreBundle\Entity\Subscription\Subscription;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubscriptionType extends AbstractType
{
    public function getBlockPrefix()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = Subscription::getStripePackageTypes();
        $builder
            ->add('package_type', Type\TextType::class, [
                'property_path' => 'packageType',
                'description' => 'Package type, can be one of '.join(', ', $choices),
            ])
            ->add('coupon', Type\TextType::class, [
                'required' => false,
            ]);
        
        $builder->get('package_type')->addModelTransformer(new KeyToValueTransformer($choices));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Subscription::class,
            'csrf_protection' => false,
        ]);
    }
}