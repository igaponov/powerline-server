<?php

namespace Civix\ApiBundle\Form\Type\Group;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Group\Link;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LinkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('url', Type\UrlType::class, [
                'description' => 'Url',
                'empty_data' => '',
            ])
            ->add('label', Type\TextType::class, [
                'description' => 'Label',
                'empty_data' => '',
            ]);

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($options) {
                $form = $event->getForm();
                /** @var Link $data */
                $data = $event->getData();
                /** @var Group $group */
                $group = $options['group'];
                if ($group && !$data->getId() && $group->getLinks()->count() >= 5) {
                    $form->addError(new FormError('Group should contain 5 links or less.'));
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['group']);
        $resolver->setAllowedTypes('group', [Group::class, 'null']);
        $resolver->setDefaults([
            'data_class' => Link::class,
            'empty_data' => function(FormInterface $form) {
                return new Link(
                    $form->get('url')->getData(),
                    $form->get('label')->getData()
                );
            },
            'group' => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
