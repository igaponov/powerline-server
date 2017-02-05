<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\Stripe\BankAccount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BankAccountType extends AbstractType
{
    public function getBlockPrefix()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('source', Type\TextType::class, [
                'description' => 'Token, returned by Stripe.js.',
            ])
            ->add('currency', Type\TextType::class, [
                'description' => 'Three-letter ISO currency code representing the default currency for the account.',
            ])
            ->add('type', Type\TextType::class, [
                'description' => 'The type of legal entity, "individual" or "company".',
            ])
            ->add('first_name', Type\TextType::class, [
                'property_path' => 'firstName',
                'description' => 'The first name of the representative of this legal entity.',
            ])
            ->add('last_name', Type\TextType::class, [
                'property_path' => 'lastName',
                'description' => 'The last name of the representative of this legal entity.',
            ])
            ->add('ssn_last_4', Type\TextType::class, [
                'property_path' => 'ssnLast4',
                'description' => 'The last 4 digits of the social security number of the representative of the legal entity.',
            ])
            ->add('business_name', Type\TextType::class, [
                'property_path' => 'businessName',
                'description' => 'The legal name of the company.',
            ])
            ->add('address_line1', Type\TextType::class, [
                'property_path' => 'addressLine1',
                'description' => 'Address line 1 (Street address/PO Box/Company name).',
            ])
            ->add('address_line2', Type\TextType::class, [
                'property_path' => 'addressLine2',
                'description' => 'Address line 2 (Apartment/Suite/Unit/Building).',
            ])
            ->add('city', Type\TextType::class, [
                'description' => 'City/Suburb/Town/Village.',
            ])
            ->add('state', Type\TextType::class, [
                'description' => 'State/Province/County.',
            ])
            ->add('postal_code', Type\TextType::class, [
                'property_path' => 'postalCode',
                'description' => 'Zip/Postal Code.',
            ])
            ->add('country', Type\TextType::class, [
                'description' => '2-letter country code.',
            ])
            ->add('dob', Type\DateType::class, [
                'description' => 'Date of birth',
                'widget' => 'single_text',
            ])
            ->add('tax_id', Type\TextType::class, [
                'property_path' => 'taxId',
                'description' => 'Tax ID',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => BankAccount::class,
            'csrf_protection' =>false,
        ]);
    }
}