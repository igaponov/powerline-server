<?php
namespace Civix\ApiBundle\Form\Type\Micropetitions;

use Civix\ApiBundle\Form\KeyToValueTransformer;
use Civix\CoreBundle\Entity\Micropetitions\Answer;
use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AnswerType extends AbstractType
{
    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = Petition::getOptionTitles();
        $builder->add('option', 'text', [
            'property_path' => 'optionId',
            'description' => 'Petition option, one of: '.implode(', ', $choices),
        ]);
        $builder->get('option')->addModelTransformer(new KeyToValueTransformer($choices))
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $data = $form->getData();
                if ($data && $data !== Petition::OPTION_ID_IGNORE) {
                    $form->addError(new FormError('User is already answered this micropetition'));
                }
            });
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Answer::class,
            'csrf_protection' => false,
        ]);
    }
}