<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\Stripe\BankAccount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BankAccountType extends AbstractType
{
    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('source', 'text', [
                'description' => 'Token, returned by Stripe.js.',
            ])
            ->add('currency', 'text', [
                'description' => 'Three-letter ISO currency code representing the default currency for the account.',
            ])
            ->add('type', 'text', [
                'description' => 'The type of legal entity, "individual" or "company".',
            ])
            ->add('first_name', 'text', [
                'property_path' => 'firstName',
                'description' => 'The first name of the representative of this legal entity.',
            ])
            ->add('last_name', 'text', [
                'property_path' => 'lastName',
                'description' => 'The last name of the representative of this legal entity.',
            ])
            ->add('ssn_last_4', 'text', [
                'property_path' => 'ssnLast4',
                'description' => 'The last 4 digits of the social security number of the representative of the legal entity.',
            ])
            ->add('business_name', 'text', [
                'property_path' => 'businessName',
                'description' => 'The legal name of the company.',
            ])
            ->add('address_line1', 'text', [
                'property_path' => 'addressLine1',
                'description' => 'Address line 1 (Street address/PO Box/Company name).',
            ])
            ->add('address_line2', 'text', [
                'property_path' => 'addressLine2',
                'description' => 'Address line 2 (Apartment/Suite/Unit/Building).',
            ])
            ->add('city', 'text', [
                'description' => 'City/Suburb/Town/Village.',
            ])
            ->add('state', 'text', [
                'description' => 'State/Province/County.',
            ])
            ->add('postal_code', 'text', [
                'property_path' => 'postalCode',
                'description' => 'Zip/Postal Code.',
            ])
            ->add('country', 'text', [
                'description' => '2-letter country code.',
            ])
            ->add('dob', 'date', [
                'description' => 'Date of birth',
                'widget' => 'single_text',
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => BankAccount::class,
            'csrf_protection' =>false,
        ]);
    }
}