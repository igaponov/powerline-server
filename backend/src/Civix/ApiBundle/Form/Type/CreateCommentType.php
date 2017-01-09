<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\BaseComment;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateCommentType extends AbstractType
{
    public function getBlockPrefix()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('parent_comment', IntegerType::class, [
                'property_path' => 'parentComment',
            ]);

        $builder->get('parent_comment')->addModelTransformer(new CallbackTransformer(
            function ($value) {
                if ($value instanceof BaseComment) {
                    return $value->getId();
                }

                return $value;
            }, function ($value) use ($options) {
                if ($value) {
                    return $options['em']->getRepository($options['data_class'])->find($value);
                }

                return $value;
            }
        ));
    }

    public function getParent()
    {
        return CommentType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('em')
            ->setAllowedTypes('em', EntityManager::class);
    }
}