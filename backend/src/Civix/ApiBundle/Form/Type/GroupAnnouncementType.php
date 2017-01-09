<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\GroupSection;
use Civix\CoreBundle\Repository\GroupSectionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Announcement form.
 */
class GroupAnnouncementType extends AbstractType
{
    /**
     * Set form fields.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('group_sections', EntityType::class, [
                'class' => GroupSection::class,
                'multiple' => true,
                'query_builder' => function (GroupSectionRepository $repository) use ($options) {
                    if ($options['group_model'] instanceof Group) {
                        return $repository->getFindByGroupQueryBuilder($options['group_model']);
                    } else {
                        return $repository->createQueryBuilder('s');
                    }
                },
                'description' => 'Array of group section\'s ids',
            ]);
    }

    /**
     * Get unique name for form.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return '';
    }

    public function getParent()
    {
        return AnnouncementType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('group_model');
        $resolver->setAllowedTypes('group_model', Group::class);
    }
}
