<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\DataTransformer\JsonToArrayTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class InviteType extends AbstractType
{
    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('users', 'textarea', [
            'description' => 'JSON array ["username1", "username2", "username3", ...]',
            'constraints' => [
                new Assert\Type(['type' => 'array']),
                new Assert\NotBlank(),
                new Assert\Count(['min' => 1])
            ],
        ]);

        $builder->get('users')->addModelTransformer(new JsonToArrayTransformer());
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }
}