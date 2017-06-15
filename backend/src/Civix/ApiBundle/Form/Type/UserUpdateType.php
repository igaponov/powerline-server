<?php

namespace Civix\ApiBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserUpdateType extends AbstractType
{
    /**
     * @var string
     */
    private $originalEmail;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('facebook_link', Type\TextType::class, [
                'property_path' => 'facebookLink',
                'description' => 'Link to a Facebook profile',
            ])
            ->add('twitter_link', Type\TextType::class, [
                'property_path' => 'twitterLink',
                'description' => 'Link to a Twitter profile'
            ])
            ->add('bio', Type\TextType::class, [
                'description' => 'Biography',
            ])
            ->add('slogan', Type\TextType::class, [
                'description' => 'Slogan',
            ])
            ->add('interests', Type\CollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'description' => 'Interests',
            ])
            ->add('sex', Type\TextType::class, [
                'description' => 'Sex',
            ])
            ->add('orientation', Type\TextType::class, [
                'description' => 'Orientation',
            ])
            ->add('race', Type\TextType::class, [
                'description' => 'Race',
            ])
            ->add('income_level', Type\TextType::class, [
                'property_path' => 'incomeLevel',
                'description' => 'Income level',
            ])
            ->add('employment_status', Type\TextType::class, [
                'property_path' => 'employmentStatus',
                'description' => 'Employment status',
            ])
            ->add('education_level', Type\TextType::class, [
                'property_path' => 'educationLevel',
                'description' => 'Education level',
            ])
            ->add('marital_status', Type\TextType::class, [
                'property_path' => 'maritalStatus',
                'description' => 'Marital status',
            ])
            ->add('religion', Type\TextType::class, [
                'description' => 'Religion',
            ])
            ->add('party', Type\TextType::class, [
                'description' => 'Party',
            ])
            ->add('philosophy', Type\TextType::class, [
                'description' => 'Philosophy',
            ])
            ->add('donor', Type\TextType::class, [
                'description' => 'Donor',
            ])
            ->add('registration', Type\TextType::class, [
                'description' => 'Registration',
            ]);

        $builder->get('email')->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $this->originalEmail = $form->getData();
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => function (FormInterface $form) {
                $groups = ['profile'];
                $data = $form->getData();
                if ($this->originalEmail !== $data->getEmail()) {
                    $groups[] = 'profile-email';
                }

                return $groups;
            },
            'allow_extra_fields' => true,
        ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }

    public function getParent()
    {
        return BaseUserType::class;
    }
}