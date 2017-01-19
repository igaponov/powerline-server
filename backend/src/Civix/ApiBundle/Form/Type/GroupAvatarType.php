<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\DataTransformer\Base64EncodedStringToUploadedFileTransformer;
use Civix\CoreBundle\Entity\Group;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class GroupAvatarType extends AbstractType
{
    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('avatar', 'textarea', [
            'required' => false,
            'description' => 'Base64-encoded content',
        ]);
        $transformer = new Base64EncodedStringToUploadedFileTransformer();
        $builder->get('avatar')->addModelTransformer($transformer);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();
            $modelData = $form->getData();
            if (!empty($data['avatar'])) {
                $modelData->setAvatarFileName('');
            }
        });
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Group::class,
            'csrf_protection' => false,
        ));
    }
}