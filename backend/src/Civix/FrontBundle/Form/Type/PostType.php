<?php

namespace Civix\FrontBundle\Form\Type;

use Civix\CoreBundle\Entity\Content\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostType extends AbstractType
{
    /**
     * Set form fields.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', null, ['label' => 'Title']);
        $builder->add('shortDescription', Type\TextareaType::class, [
            'label' => 'Short description',
            'attr' => ['class' => 'span11 text-control'],
        ]);
        $builder->add('content', Type\TextareaType::class, [
            'label' => 'Post',
            'attr' => ['class' => 'span11 text-control', 'data-provide' => 'markdown'],
        ]);
        $builder->add('image', Type\FileType::class, ['label' => 'Post image', 'required' => false]);
        $builder->add('createdAt', Type\DateType::class, ['label' => 'Date']);
    }

    /**
     * Set default form option.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
            'validation_groups' => ['blog-post'],
        ]);
    }
}
