<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\Announcement;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\GroupSection;
use Civix\CoreBundle\Repository\GroupSectionRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Announcement form.
 */
class AnnouncementType extends AbstractType
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
            ->add('content', 'textarea', [
                'description' => 'Message. The limit is 250 symbols. Long hyperlinks will be cut to 20 symbols.',
                'required' => true,
            ])
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

    /**
     * Set default form option.
     *
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Announcement::class,
            'csrf_protection' => false,
        ]);
    }
}
