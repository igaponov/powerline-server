<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\LeaderContentRootInterface;
use Civix\CoreBundle\Entity\OfficialInterface;
use Civix\CoreBundle\Entity\Stripe\BankAccount;
use Civix\CoreBundle\Entity\Stripe\Card;
use Doctrine\ORM\EntityManager;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Stripe\Customer;
use Civix\CoreBundle\Entity\Stripe\CustomerInterface;
use Civix\CoreBundle\Entity\Stripe\AccountInterface;
use Civix\CoreBundle\Entity\Stripe\Charge;
use Civix\CoreBundle\Entity\Poll\Question\PaymentRequest;
use Civix\CoreBundle\Entity\Subscription\Subscription;
use Stripe\Account;
use Stripe\Coupon;
use Stripe\Error;

class Stripe
{
    const VOLUME_TRANSACTION_FEE = 0.05; // 5%
    const PER_TRANSACTION_FEE = 50; // 50 cents

    private $em;

    public function __construct($apiKey, EntityManager $em)
    {
        $this->em = $em;
        \Stripe\Stripe::setApiKey($apiKey);
    }

    public function addCard(CustomerInterface $customer, Card $card)
    {
        /** @var \Stripe\Customer|\stdClass $stripeCustomer */
        $stripeCustomer = $this->getStripeCustomer($customer);
        $stripeCustomer->source = $card->getSource();
        $stripeCustomer->save();
    }

    /**
     * @param CustomerInterface $customer
     * @return \Stripe\Customer|\stdClass
     */
    public function getStripeCustomer(CustomerInterface $customer)
    {
        return \Stripe\Customer::retrieve($customer->getId());
    }

    /**
     * @param AccountInterface $account
     * @return Account
     */
    public function getStripeAccount(AccountInterface $account)
    {
        return Account::retrieve($account->getId());
    }

    public function getCards(CustomerInterface $customer)
    {
        return $this->getStripeCustomer($customer)
            ->sources->all(['object' => 'card']);
    }

    public function getBankAccounts(AccountInterface $account)
    {
        return $this->getStripeAccount($account)
            ->bank_accounts;
    }

    public function addBankAccount(AccountInterface $account, BankAccount $bankAccount)
    {
        /** @var Account|\stdClass $sa */
        $sa = $this->getStripeAccount($account);

        $sa->bank_account = $bankAccount->getSource();
        $sa->email = $bankAccount->getEmail();
        $sa->default_currency = $bankAccount->getCurrency();

        $sa->legal_entity->type = $bankAccount->getType();
        $sa->legal_entity->first_name = $bankAccount->getFirstName();
        $sa->legal_entity->last_name = $bankAccount->getLastName();
        // Stripe: SSN last 4 is only available for US accounts.
        if ($bankAccount->getCountry() === 'US') {
            $sa->legal_entity->ssn_last_4 = $bankAccount->getSsnLast4();
        }
        $sa->legal_entity->business_name = $bankAccount->getBusinessName();
        $sa->legal_entity->business_tax_id = $bankAccount->getTaxId();

        $sa->legal_entity->address = [
            'line1' => $bankAccount->getAddressLine1(),
            'line2' => $bankAccount->getAddressLine2(),
            'city' => $bankAccount->getCity(),
            'state' => $bankAccount->getState(),
            'postal_code' => $bankAccount->getPostalCode(),
            'country' => $bankAccount->getCountry(),
        ];
        if ($dob = $bankAccount->getDob()) {
            $sa->legal_entity->dob = [
                'day' => $dob->format('d'),
                'month' => $dob->format('m'),
                'year' => $dob->format('Y'),
            ];
        }

        $sa->save();
    }

    public function removeCard(CustomerInterface $customer, Card $card)
    {
        /** @var \Stripe\Card $card */
        $card = $this->getStripeCustomer($customer)->sources->retrieve($card->getId());
        $card->delete();
    }

    public function removeBankAccount(AccountInterface $account, BankAccount $bankAccount)
    {
        $this->getStripeAccount($account)
            ->external_accounts->retrieve($bankAccount->getId())->delete();
    }

    public function hasPayoutAccount(LeaderContentRootInterface $root)
    {
        $account = $root->getStripeAccount();

        if ($account) {
            return count($account->getBankAccounts());
        }

        return false;
    }

    public function hasCard(OfficialInterface $official)
    {
        $customer = $official->getStripeCustomer();

        return $customer && count($customer->getCards());
    }

    /**
     * @param Charge $charge
     * @return \Stripe\Charge
     */
    public function chargeToPaymentRequest(Charge $charge)
    {
        $paymentRequest = $charge->getQuestion();
        $customer = $charge->getFromCustomer();
        $account = $charge->getToAccount();

        if (!$paymentRequest instanceof PaymentRequest) {
            throw new \RuntimeException('Only an answer to payment request can be charged.');
        }

        $amount = $charge->getAmount();

        $sc = \Stripe\Charge::create([
            'amount' => $amount,
            'application_fee' => ceil($amount * self::VOLUME_TRANSACTION_FEE + self::PER_TRANSACTION_FEE),
            'currency' => 'usd',
            'customer' => $customer->getStripeId(),
            'statement_descriptor' => 'PowerlinePay-'.
                                        $this->getAppearsOnStatement($paymentRequest->getOwner()),
            'destination' => $account->getStripeId(),
            'description' => sprintf(
                'Powerline Payment: (%s) - (%s)',
                $paymentRequest->getOwner()->getOfficialTitle(),
                $paymentRequest->getTitle()
            )
        ]);

        return $sc;
    }

    public function chargeCustomer(Customer $customer, $amount, $statement = null, $description = null)
    {
        $charge = new Charge($customer);

        $sc = \Stripe\Charge::create([
            'amount' => $amount,
            'currency' => 'usd',
            'customer' => $customer->getId(),
            'statement_descriptor' => $statement,
            'description' => $description,
        ]);

        $charge->updateStripeData($sc);
        $this->em->persist($charge);
        $this->em->flush($charge);

        return $charge;
    }

    public function chargeUser(OfficialInterface $official, $amount, $statement = null, $description = null)
    {
        $customer = $official->getStripeCustomer();

        return $this->chargeCustomer($customer, $amount, $statement, $description);
    }

    /**
     * @param Subscription $subscription
     * @return Subscription
     */
    public function handleSubscription(Subscription $subscription)
    {
        $user = $subscription->getUserEntity();
        $customer = $user->getStripeCustomer();
        if (!$customer->getId()) {
            throw new \RuntimeException('User doesn\'t have an account in stripe');
        }

        $stripeCustomer = $this->getStripeCustomer($customer);

        if ($subscription->getStripeId()) {
            try {
                /** @var \Stripe\Subscription|\stdClass $stripeSubscription */
                $stripeSubscription = $stripeCustomer->subscriptions
                    ->retrieve($subscription->getStripeId());
                $stripeSubscription->plan = $subscription->getPlanId();
                $stripeSubscription->coupon = $subscription->getCoupon();
                $stripeSubscription->save();
            } catch (Error\InvalidRequest $e) {
                if (404 === $e->getHttpStatus()) {
                    $subscription->stripeReset();
                }
                $stripeSubscription = $stripeCustomer->subscriptions
                    ->create([
                        'plan' => $subscription->getPlanId(),
                        'coupon' => $subscription->getCoupon(),
                    ]);
            }
        } else {
            $stripeSubscription = $stripeCustomer->subscriptions
                ->create([
                    'plan' => $subscription->getPlanId(),
                    'coupon' => $subscription->getCoupon(),
                ]);
        }

        $subscription->syncStripeData($stripeSubscription);
        $this->em->persist($subscription);
        $this->em->flush($subscription);

        return $subscription;
    }

    public function cancelSubscription(Subscription $subscription)
    {
        if (!$subscription->getStripeId()) {
            $subscription->setEnabled(false);
            $this->em->flush($subscription);

            return $subscription;
        }
        $user = $subscription->getUserEntity();
        $customer = $user->getStripeCustomer();
        $stripeCustomer = $this->getStripeCustomer($customer);

        try {
            /** @var \Stripe\Subscription $ss */
            $ss = $stripeCustomer->subscriptions
                ->retrieve($subscription->getStripeId());
            $ss->cancel(['at_period_end' => true]);
            $subscription->syncStripeData($ss);
        } catch (Error\InvalidRequest $e) {
            if (404 === $e->getHttpStatus()) {
                $subscription->stripeReset();
            }
        }

        $this->em->flush($subscription);

        return $subscription;
    }

    public function syncSubscription(Subscription $subscription)
    {
        $user = $subscription->getUserEntity();
        $customer = $user->getStripeCustomer();
        $stripeCustomer = $this->getStripeCustomer($customer);

        try {
            $ss = $stripeCustomer->subscriptions
                ->retrieve($subscription->getStripeId());
            $subscription->syncStripeData($ss);
        } catch (Error\InvalidRequest $e) {
            if (404 === $e->getHttpStatus()) {
                $subscription->stripeReset();
            }
        }

        $this->em->flush($subscription);

        return $subscription;
    }

    public function getCoupons($limit, $after = null, $before = null)
    {
        return Coupon::all([
            'limit' => $limit,
            'starting_after' => $after,
            'ending_before' => $before,
        ]);
    }

    /**
     * @param OfficialInterface $official
     * @return \Stripe\Customer|\stdClass
     */
    public function createCustomer(OfficialInterface $official)
    {
        return \Stripe\Customer::create([
            'description' => $official->getOfficialName(),
            'email' => $official->getEmail(),
        ]);
    }

    /**
     * @param LeaderContentRootInterface $root
     * @return Account|\stdClass
     */
    public function createAccount(LeaderContentRootInterface $root)
    {
        $params = [
            'managed' => true,
            'metadata' => ['id' => $root->getId(), 'type' => $root->getType()],
            'email' => $root->getEmail(),
        ];
        if ($root->getUser() && $root->getUser()->getCountry()) {
            $params['country'] = $root->getUser()->getCountry();
        }

        return Account::create($params);
    }

    /**
     * @param AccountInterface $account
     */
    public function deleteAccount(AccountInterface $account)
    {
        Account::retrieve($account->getId())->delete();
    }

    private function getAppearsOnStatement(LeaderContentRootInterface $root)
    {
        if ($root instanceof Group) {
            return $root->getAcronym() ?: mb_substr($root->getOfficialName(), 0, 5);
        }

        return 'PowerlineAppPay';
    }
}
