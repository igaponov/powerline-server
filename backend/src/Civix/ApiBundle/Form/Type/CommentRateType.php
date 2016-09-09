<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\KeyToValueTransformer;
use Civix\CoreBundle\Entity\BaseCommentRate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CommentRateType extends AbstractType
{
    /**
     * @var string
     */
    private $entityClass;

    public function __construct($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = BaseCommentRate::getRateValueLabels();
        $builder
            ->add('rate_value', 'text', [
                'property_path' => 'rateValue',
                'description' => 'Rate, one of: '.implode(', ', $choices),
            ]);
        $builder->get('rate_value')->addModelTransformer(new KeyToValueTransformer($choices));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->entityClass,
            'csrf_protection' => false,
        ]);
    }
}