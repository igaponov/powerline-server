<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\Component\Notification\Model\AbstractEndpoint;
use Civix\Component\Notification\Model\AndroidEndpoint;
use Civix\Component\Notification\Model\IOSEndpoint;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EndpointType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = AbstractEndpoint::getTypes();
        $builder
            ->add('type', ChoiceType::class, [
                'description' => 'Device type',
                'choices' => $choices,
                'mapped' => false,
            ])
            ->add('token', TextType::class, [
                'description' => 'Token',
            ]);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();
            switch ($data['type'] ?? null) {
                case AbstractEndpoint::TYPE_IOS:
                    $form->setData(new IOSEndpoint());
                    break;
                case AbstractEndpoint::TYPE_ANDROID:
                    $form->setData(new AndroidEndpoint());
                    break;
                default:
                    throw new \InvalidArgumentException('Invalid endpoint type.');
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AbstractEndpoint::class,
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}