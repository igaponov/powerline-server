<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\File;
use Civix\CoreBundle\Entity\UserPetition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserPetitionUpdateType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('body', TextareaType::class, [
                'empty_data' => '',
            ])
            // @todo for compatibility with old endpoints
            ->add('petition_body', null, [
                'description' => 'For compatibility with old endpoints.',
                'mapped' => false,
            ])
            ->add('image', EncodedFileType::class, [
                'description' => 'Base64-encoded attachment',
                'data_class' => File::class,
                'required' => false,
            ])
        ;
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!empty($data['petition_body'])) {
                $data['body'] = $data['petition_body'];
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserPetition::class,
        ]);
    }
}