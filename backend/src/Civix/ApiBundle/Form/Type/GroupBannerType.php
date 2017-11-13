<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\File;
use Civix\CoreBundle\Entity\Group;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupBannerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('banner', EncodedFileType::class, [
            'description' => 'Base64-encoded content',
            'data_class' => File::class,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Group::class,
            'validation_groups' => 'banner',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}