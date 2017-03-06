<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\KeyToValueTransformer;
use Civix\CoreBundle\Entity\DiscountCode;
use Civix\CoreBundle\Entity\Subscription\Subscription;
use Civix\CoreBundle\Model\Coupon;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubscriptionType extends AbstractType
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

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
        $repository = $this->em->getRepository(DiscountCode::class);
        $builder->get('coupon')->addModelTransformer(new CallbackTransformer(
            function () {
                return null;
            },
            function ($value) use ($repository) {
                return new Coupon($repository->findOriginalCodeByUserAndCode($value) ? : $value);
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Subscription::class,
            'csrf_protection' => false,
        ]);
    }
}