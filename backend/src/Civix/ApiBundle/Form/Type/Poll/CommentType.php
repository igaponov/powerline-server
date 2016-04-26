<?php
namespace Civix\ApiBundle\Form\Type\Poll;

use Civix\CoreBundle\Entity\Poll\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CommentType extends AbstractType
{
    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('comment_body', 'textarea', [
                'property_path' => 'commentBody',
            ])
            ->add('parent_comment', 'entity', [
                'class' => Comment::class,
                'property_path' => 'parentComment',
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
            'csrf_protection' => false,
        ]);
    }
}