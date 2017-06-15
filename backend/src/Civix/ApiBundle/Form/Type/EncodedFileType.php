<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\Component\ContentConverter\ConverterInterface;
use Civix\CoreBundle\Model\TempFile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EncodedFileType extends AbstractType
{
    /**
     * @var ConverterInterface
     */
    private $converter;

    public function __construct(ConverterInterface $converter)
    {
        $this->converter = $converter;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();
                $data = $this->converter->convert((string)$data);
                if ($data) {
                    $event->setData(new TempFile($data));
                } else {
                    $event->setData($event->getForm()->getData());
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => File::class,
            'empty_data' => null,
            'compound' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'encoded_file';
    }
}