<?php
namespace Civix\ApiBundle\Form\Type\Poll;

use Civix\CoreBundle\Entity\Poll\Comment;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentType extends AbstractType
{
    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('comment_body', Type\TextType::class, [
                'property_path' => 'commentBody',
            ])
            ->add('parent_comment', EntityType::class, [
                'class' => Comment::class,
                'property_path' => 'parentComment',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
            'csrf_protection' => false,
        ]);
    }
}