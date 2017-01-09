<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\UserPetition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserPetitionUpdateType extends AbstractType
{
    public function getBlockPrefix()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('body')
            ->add('petition_body', null, [
                'mapped' => false,
            ])
        ;
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!empty($data['petition_body'])) {
                $data['body'] = $data['petition_body'];
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserPetition::class,
            'csrf_protection' => false,
        ]);
    }
}