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

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($options) {
                $data = $event->getData();
                $data = $this->converter->convert((string)$data);
                if ($data) {
                    $data = new TempFile($data);
                    if ($options['data_class'] === \Civix\CoreBundle\Entity\File::class) {
                        $data = new \Civix\CoreBundle\Entity\File($data);
                    }
                } else {
                    $data = $event->getForm()->getData();
                }
                $event->setData($data);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => File::class,
            'empty_data' => null,
            'compound' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'encoded_file';
    }
}