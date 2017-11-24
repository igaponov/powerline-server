<?php

namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Validator\Constraints\AuthyCodeProperty;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class BaseUserType extends AbstractType
{
    /**
     * @var string
     */
    private $originalPhone;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('first_name', Type\TextType::class, [
                'property_path' => 'firstName',
                'description' => 'First name',
            ])
            ->add('last_name', Type\TextType::class, [
                'property_path' => 'lastName',
                'description' => 'Last name',
            ])
            ->add('email', Type\TextType::class, [
                'description' => 'Email',
            ])
            ->add('birth', Type\DateTimeType::class, [
                'widget' => 'single_text',
                'description' => 'Birthday',
            ])
            ->add('address1', Type\TextType::class, [
                'description' => 'Address 1',
            ])
            ->add('address2', Type\TextType::class, [
                'description' => 'Address 2',
            ])
            ->add('city', Type\TextType::class, [
                'description' => 'City',
            ])
            ->add('state', Type\TextType::class, [
                'description' => 'State',
            ])
            ->add('zip', Type\TextType::class, [
                'description' => 'Zip code',
            ])
            ->add('country', Type\TextType::class, [
                'description' => 'Country',
            ])
            ->add('phone', PhoneNumberType::class, [
                'description' => 'Phone number',
            ])
            ->add('code', Type\TextType::class, [
                'mapped' => false,
                'required' => false,
                'description' => 'Code for a phone verification. Submit only if the phone was changed. Use /api-public/phone/verification endpoint to start a verification.'
            ])
            ->add('avatar_file_name', EncodedFileType::class, [
                'property_path' => 'avatar',
                'description' => 'Avatar, can be an url or a base64-encoded string',
            ]);

        $builder->get('phone')->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $this->originalPhone = $form->getData();
            }
        );
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();
                $phoneUtil = PhoneNumberUtil::getInstance();
                if (!empty($data['phone']) && $form->getData()) {
                    $phoneNumber = $phoneUtil->parse($data['phone'], null);
                    $phone = $form->getData()->getPhone();
                    if (!$phone instanceof PhoneNumber || !$phone->equals($phoneNumber)) {
                        $form->add('code', Type\TextType::class, [
                            'mapped' => false,
                            'constraints' => [
                                new NotBlank(['groups' => ['profile']]),
                                new AuthyCodeProperty(['phoneValue' => $phone, 'groups' => ['profile']]),
                            ],
                        ]);
                    }
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }

}