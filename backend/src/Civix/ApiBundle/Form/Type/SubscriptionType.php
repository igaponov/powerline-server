<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\KeyToValueTransformer;
use Civix\CoreBundle\Entity\Subscription\Subscription;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SubscriptionType extends AbstractType
{
    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = Subscription::getStripePackageTypes();
        $builder
            ->add('package_type', 'text', [
                'property_path' => 'packageType',
                'description' => 'Package type, can be one of '.join(', ', $choices),
            ])
            ->add('coupon', 'text', [
                'required' => false,
            ]);
        
        $builder->get('package_type')->addModelTransformer(new KeyToValueTransformer($choices));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Subscription::class,
            'csrf_protection' => false,
        ]);
    }
}