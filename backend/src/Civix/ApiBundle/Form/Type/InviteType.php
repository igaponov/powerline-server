<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\DataTransformer\JsonToArrayTransformer;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserInterface;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Repository\PostRepository;
use Civix\CoreBundle\Repository\UserPetitionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class InviteType extends AbstractType
{
    /**
     * @var UserInterface
     */
    private $user;

    public function getBlockPrefix()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->user = $options['user_model'];
        $builder
            ->add('users', Type\TextareaType::class, [
                'description' => 'JSON array ["username1", "username2", "username3", ...]',
                'constraints' => [
                    new Assert\Type(['type' => 'array']),
                    new Assert\Count(['min' => 1])
                ],
                'required' => false,
            ])
            ->add('post', EntityType::class, [
                'class' => Post::class,
                'query_builder' => function (PostRepository $repository) {
                    if ($this->user) {
                        return $repository->getForInviteQueryBuilder($this->user);
                    } else {
                        return $repository->createQueryBuilder('p');
                    }
                },
                'description' => "ID of a post to invite post's upvoters",
                'required' => false,
            ])
            ->add('user_petition', EntityType::class, [
                'class' => UserPetition::class,
                'query_builder' => function (UserPetitionRepository $repository) {
                    if ($this->user) {
                        return $repository->getForInviteQueryBuilder($this->user);
                    } else {
                        return $repository->createQueryBuilder('p');
                    }
                },
                'description' => "ID of a user petition to invite petition's signers",
                'required' => false,
            ]);

        $builder->get('users')->addModelTransformer(new JsonToArrayTransformer());
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['user_model'])
            ->setAllowedTypes('user_model', ['null', User::class])
            ->setDefaults([
                'user_model' => null,
                'csrf_protection' => false,
            ]);
    }
}