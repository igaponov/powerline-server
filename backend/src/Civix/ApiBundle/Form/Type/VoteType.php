<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\KeyToValueTransformer;
use Civix\CoreBundle\Entity\Post\Vote;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VoteType extends AbstractType
{
    public function getBlockPrefix()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = Vote::getOptionTitles();
        $builder->add('option', TextType::class, [
            'description' => 'Option, one of: '.implode(', ', $choices),
        ]);
        $builder->get('option')->addModelTransformer(new KeyToValueTransformer($choices))
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $data = $form->getData();
                if ($data && $data !== Vote::OPTION_IGNORE) {
                    $form->addError(new FormError('User is already answered this petition'));
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Vote::class,
            'csrf_protection' => false,
        ]);
    }
}