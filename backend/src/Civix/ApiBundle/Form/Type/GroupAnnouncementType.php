<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\GroupSection;
use Civix\CoreBundle\Repository\GroupSectionRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Announcement form.
 */
class GroupAnnouncementType extends AbstractType
{
    /**
     * @var Group
     */
    private $group;

    public function __construct(Group $group)
    {
        $this->group = $group;
    }

    /**
     * Set form fields.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('group_sections', 'entity', [
                'class' => GroupSection::class,
                'multiple' => true,
                'query_builder' => function (GroupSectionRepository $repository) {
                    if ($this->group instanceof Group) {
                        return $repository->getFindByGroupQueryBuilder($this->group);
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
    public function getName()
    {
        return '';
    }

    public function getParent()
    {
        return new AnnouncementType();
    }
}
