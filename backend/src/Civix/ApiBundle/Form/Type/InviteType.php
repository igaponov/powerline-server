<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\DataTransformer\JsonToArrayTransformer;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserInterface;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Repository\PostRepository;
use Civix\CoreBundle\Repository\UserPetitionRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class InviteType extends AbstractType
{
    /**
     * @var UserInterface
     */
    private $user;

    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }

    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('users', 'textarea', [
                'description' => 'JSON array ["username1", "username2", "username3", ...]',
                'constraints' => [
                    new Assert\Type(['type' => 'array']),
                    new Assert\Count(['min' => 1])
                ],
            ])
            ->add('post', 'entity', [
                'class' => Post::class,
                'query_builder' => function (PostRepository $repository) {
                    if ($this->user) {
                        return $repository->getForInviteQueryBuilder($this->user);
                    } else {
                        return $repository->createQueryBuilder('p');
                    }
                },
                'description' => "ID of a post to invite post's upvoters",
            ])
            ->add('user_petition', 'entity', [
                'class' => UserPetition::class,
                'query_builder' => function (UserPetitionRepository $repository) {
                    if ($this->user) {
                        return $repository->getForInviteQueryBuilder($this->user);
                    } else {
                        return $repository->createQueryBuilder('p');
                    }
                },
                'description' => "ID of a user petition to invite petition's signers",
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