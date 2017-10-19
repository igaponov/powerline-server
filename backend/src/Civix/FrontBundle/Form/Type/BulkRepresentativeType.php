<?php

namespace Civix\FrontBundle\Form\Type;

use Civix\FrontBundle\Model\BulkRepresentative;
use Mopa\Bundle\BootstrapBundle\Form\Type\FormActionsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BulkRepresentativeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('text', TextType::class, [
                'label' => 'CSV format',
                'data' => 'First name, last name, official title, phone, fax, email, website, country, state, city, address line 1, address line 2, address line 3, district, party, birthday, start term, end term, contact form, missed votes, votes with party, facebook, youtube, twitter, bioguide',
                'static_text' => true,
                'mapped' => false,
            ])
            ->add('file', FileType::class, [
                'label' => 'CSV file',
            ])
            ->add('buttons', FormActionsType::class, [
                'button_offset' => 'col-sm-offset-3 col-sm-9',
                'buttons' => [
                    'submit' => [
                        'type' => SubmitType::class,
                    ],
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BulkRepresentative::class,
        ]);
    }
}