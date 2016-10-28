<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\Stripe\BankAccount;
use Civix\CoreBundle\Entity\Stripe\Card;
use Doctrine\ORM\EntityManager;
use Civix\CoreBundle\Entity\UserInterface;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Stripe\Customer;
use Civix\CoreBundle\Entity\Stripe\CustomerInterface;
use Civix\CoreBundle\Entity\Stripe\AccountInterface;
use Civix\CoreBundle\Entity\Stripe\Account;
use Civix\CoreBundle\Entity\Stripe\Charge;
use Civix\CoreBundle\Entity\Poll\Question\PaymentRequest;
use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Subscription\Subscription;
use Stripe\Error;

class Stripe
{
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
        return \Stripe\Customer::retrieve($customer->getStripeId());
    }

    public function getCards(CustomerInterface $customer)
    {
        return $this->getStripeCustomer($customer)
            ->sources->all(['object' => 'card']);
    }

    public function getBankAccounts(AccountInterface $account)
    {
        return \Stripe\Account::retrieve($account->getStripeId())
            ->bank_accounts;
    }

    public function addBankAccount(AccountInterface $account, BankAccount $bankAccount)
    {
        /** @var \Stripe\Account|\stdClass $sa */
        $sa = \Stripe\Account::retrieve($account->getStripeId());

        $sa->bank_account = $bankAccount->getSource();
        $sa->email = $bankAccount->getEmail();
        $sa->default_currency = $bankAccount->getCurrency();

        $sa->legal_entity->type = $bankAccount->getType();
        $sa->legal_entity->first_name = $bankAccount->getFirstName();
        $sa->legal_entity->last_name = $bankAccount->getLastName();
        $sa->legal_entity->ssn_last_4 = $bankAccount->getSsnLast4();
        $sa->legal_entity->business_name = $bankAccount->getBusinessName();

        $sa->legal_entity->address = [
            'line1' => $bankAccount->getAddressLine1(),
            'line2' => $bankAccount->getAddressLine2(),
            'city' => $bankAccount->getCity(),
            'state' => $bankAccount->getState(),
            'postal_code' => $bankAccount->getPostalCode(),
            'country' => $bankAccount->getCountry(),
        ];

        $sa->save();
    }

    public function removeCard(CustomerInterface $customer, Card $card)
    {
        $this->getStripeCustomer($customer)
            ->sources->retrieve($card->getId())->delete();
    }

    public function hasPayoutAccount(UserInterface $user)
    {
        $account = $this->em
            ->getRepository(Account::getEntityClassByUser($user))
            ->findOneBy(['user' => $user])
        ;
        if ($account) {
            return count($account->getBankAccounts());
        }

        return false;
    }

    public function hasCard(UserInterface $user)
    {
        $customer = $this->em
            ->getRepository(Customer::getEntityClassByUser($user))
            ->findOneBy(['user' => $user])
        ;

        return $customer && count($customer->getCards());
    }

    public function chargeToPaymentRequest(Answer $answer)
    {
        $user = $answer->getUser();
        $paymentRequest = $answer->getQuestion();

        if (!$paymentRequest instanceof PaymentRequest) {
            throw new \RuntimeException('Only an answer to payment request can be charged.');
        }
        /** @var Customer $customer */
        $customer = $this->em
            ->getRepository(Customer::getEntityClassByUser($user))
            ->findOneBy(['user' => $user]);

        if (!$customer) {
            throw new \RuntimeException('User doesn\'t have an account in stripe');
        }

        $account = $this->em
            ->getRepository(Account::getEntityClassByUser($paymentRequest->getOwner()))
            ->findOneBy(['user' => $paymentRequest->getOwner()])
        ;

        if (!$account) {
            throw new \RuntimeException('Group doesn\'t have an account in stripe');
        }

        $charge = new Charge($customer, $account, $paymentRequest);
        $amount = $answer->getCurrentPaymentAmount() * 100;

        $sc = \Stripe\Charge::create([
            'amount' => $amount,
            'application_fee' => ceil($amount * 0.021 + 50),
            'currency' => 'usd',
            'customer' => $customer->getStripeId(),
            'statement_descriptor' => 'PowerlinePay-'.
                                        $this->getAppearsOnStatement($paymentRequest->getOwner()),
            'destination' => $account->getStripeId(),
            'description' => 'Powerline Payment: ('.$paymentRequest->getOwner()->getOfficialName()
                                        .') - ('.$paymentRequest->getTitle().')',
        ]);

        $charge->updateStripeData($sc);
        $this->em->persist($charge);
        $this->em->flush($charge);

        return $charge;
    }

    public function chargeCustomer(Customer $customer, $amount, $statement = null, $description = null)
    {
        $charge = new Charge($customer);

        $sc = \Stripe\Charge::create([
            'amount' => $amount,
            'currency' => 'usd',
            'customer' => $customer->getStripeId(),
            'statement_descriptor' => $statement,
            'description' => $description,
        ]);

        $charge->updateStripeData($sc);
        $this->em->persist($charge);
        $this->em->flush($charge);

        return $charge;
    }

    public function chargeUser(UserInterface $user, $amount, $statement = null, $description = null)
    {
        $customer = $this->em
            ->getRepository(Customer::getEntityClassByUser($user))
            ->findOneBy(['user' => $user]);

        return $this->chargeCustomer($customer, $amount, $statement, $description);
    }

    /**
     * @param Subscription $subscription
     * @return Subscription
     */
    public function handleSubscription(Subscription $subscription)
    {
        $user = $subscription->getUserEntity();
        /** @var Customer $customer */
        $customer = $this->em
            ->getRepository(Customer::getEntityClassByUser($user))
            ->findOneBy(['user' => $user]);
        if (!$customer) {
            throw new \RuntimeException('User doesn\'t have an account in stripe');
        }

        $stripeCustomer = $this->getStripeCustomer($customer);

        if ($subscription->getStripeId()) {
            try {
                /** @var \Stripe\Subscription $stripeSubscription */
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
        $customer = $this->em
            ->getRepository(Customer::getEntityClassByUser($user))
            ->findOneBy(['user' => $user]);
        $stripeCustomer = $this->getStripeCustomer($customer);

        try {
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
        $customer = $this->em
            ->getRepository(Customer::getEntityClassByUser($user))
            ->findOneBy(['user' => $user]);
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
        return \Stripe\Coupon::all([
            'limit' => $limit,
            'starting_after' => $after,
            'ending_before' => $before,
        ]);
    }

    /**
     * @param UserInterface $user
     * @return \Stripe\Customer|\stdClass
     */
    public function createCustomer(UserInterface $user)
    {
        return \Stripe\Customer::create([
            'description' => $user->getOfficialName(),
            'email' => $user->getEmail(),
        ]);
    }

    /**
     * @param Group $user
     * @return \Stripe\Account|\stdClass
     */
    public function createAccount(Group $user)
    {
        return \Stripe\Account::create([
            'managed' => true,
            'metadata' => ['id' => $user->getId(), 'type' => $user->getType()],
            'email' => $user->getEmail(),
        ]);
    }

    private function getAppearsOnStatement(UserInterface $user)
    {
        if ($user instanceof Group) {
            return $user->getAcronym() ?: mb_substr($user->getOfficialName(), 0, 5);
        }

        return 'PowerlineAppPay';
    }
}
