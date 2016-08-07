<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\Poll\EducationalContext;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EducationalContextType extends AbstractType
{
    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', 'text')
            ->add('content', 'textarea', [
                'description' => 'Base64',
            ]);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            /** @var EducationalContext $data */
            $data = $event->getData();
            if (!empty($data['content']) && $data['type'] == EducationalContext::IMAGE_TYPE) {
                $content = base64_decode($data['content'], true);
                $path = tempnam(sys_get_temp_dir(), 'upload');
                file_put_contents($path, $content);
                $file = new UploadedFile($path, 'content', null, mb_strlen($content), null, true);
                $data['content'] = $file;
            }
            $event->setData($data);
        });
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
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