<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\Announcement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Announcement form.
 */
class AnnouncementType extends AbstractType
{
    /**
     * Set form fields.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'description' => 'Message. The limit is 250 symbols. Long hyperlinks will be cut to 20 symbols.',
                'required' => true,
            ]);
    }

    /**
     * Get unique name for form.
     *
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return '';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Announcement::class,
        ]);
    }
}
