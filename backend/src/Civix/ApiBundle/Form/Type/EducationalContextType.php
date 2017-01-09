<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Helper\Base64ToFileConverter;
use Civix\CoreBundle\Entity\Poll\EducationalContext;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EducationalContextType extends AbstractType
{
    public function getBlockPrefix()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', Type\TextType::class, [
                'description' => 'Type, one of: '.implode(', ', EducationalContext::getTypes()),
            ])
            ->add('content', Type\TextareaType::class, [
                'description' => 'Base64-encoded content',
            ]);
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                /** @var EducationalContext $data */
                $data = $event->getData();
                if ($data['type'] === EducationalContext::IMAGE_TYPE) {
                    $data['content'] = Base64ToFileConverter::convert($data['content']);
                    $event->setData($data);
                }
            },
            1
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EducationalContext::class,
            'csrf_protection' => false,
            'validation_groups' => function (FormInterface $form) {
                $data = $form->getData();
                $groups = ['Default', 'context'];
                if (EducationalContext::IMAGE_TYPE == $data->getType()) {
                    $groups[] = 'image';
                }
                $groups[] = 'text';

                return $groups;
            },
        ]);
    }
}