<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Entity\AnnouncementCollection;
use Civix\CoreBundle\Entity\Announcement;
use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnnouncementsType extends AbstractType implements ChoiceLoaderInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var User
     */
    private $user;

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
        $this->user = $options['user_model'];
        $builder
            ->add('announcements', ChoiceType::class, [
                'multiple' => true,
                'choice_loader' => $this,
                'description' => 'Collection of announcement\'s IDs for update.',
            ])
            ->add('read', CheckboxType::class, [
                'description' => 'Mark announcements as read',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['user_model']);
        $resolver->setAllowedTypes('user_model', [User::class, 'null']);
        $resolver->setDefaults([
            'user_model' => null,
            'data_class' => AnnouncementCollection::class,
            'empty_data' => function(FormInterface $form) {
                $collection = new AnnouncementCollection(
                    ...$form->get('announcements')->getData() ? : []
                );
                $collection->setRead($form->get('read')->getData());

                return $collection;
            },
            'csrf_protection' => false,
        ]);
    }

    public function loadChoiceList($value = null)
    {
        return new ArrayChoiceList([]);
    }

    public function loadChoicesForValues(array $values, $value = null)
    {
        $expr = $this->em->getExpressionBuilder();
        $qb = $this->em->getRepository(Announcement::class)
            ->createQueryBuilder('a')
            ->where($expr->in('a.id', ':ids'))
            ->setParameter(':ids', $values);
        if ($this->user) {
            $qb->leftJoin('a.announcementRead', 'ar', 'WITH', 'ar.user = :user')
                ->setParameter(':user', $this->user)
                ->andWhere('ar.user IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    public function loadValuesForChoices(array $choices, $value = null)
    {
        return [];
    }
}