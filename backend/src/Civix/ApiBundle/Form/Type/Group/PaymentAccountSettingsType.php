<?php

namespace Civix\ApiBundle\Form\Type\Group;

use Civix\CoreBundle\Entity\Customer\Customer;
use Civix\FrontBundle\Form\Model\PaymentAccountSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PaymentAccountSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $pieces = Customer::getAccountTypes();
        $builder
            ->add('account_type', 'text', [
                'property_path' => 'accountType',
                'description' => 'Account type. One of: '.implode(', ', $pieces)
            ])
            ->add('business_name', 'text', [
                'property_path' => 'businessName',
                'description' => 'Business Name',
            ])
            ->add('ein', 'number', [
                'description' => 'Employer Identification Number',
            ])
            ->add('name', 'text', [
                'description' => 'Name',
            ])
            ->add('birth', 'date', [
                'description' => 'Date of Birth',
                'widget' => 'single_text',
            ])
            ->add('ssn_last_4', 'number', [
                'property_path' => 'SSNLast4',
                'description' => 'Last four digits of the Social Security Number',
            ])
        ;
    }

    public function getName()
    {
        return '';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => PaymentAccountSettings::class,
            'csrf_protection' => false,
            'validation_groups' => function(Form $form) {
                /** @var PaymentAccountSettings $settings */
                $settings = $form->getData();
                
                return in_array($settings->getAccountType(), Customer::getAccountTypes()) ?
                    $settings->getAccountType() : "Default";
            },
        ));
    }
}
