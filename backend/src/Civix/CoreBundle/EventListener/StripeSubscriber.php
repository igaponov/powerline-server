<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Stripe\Charge;
use Civix\CoreBundle\Event\AccountEvent;
use Civix\CoreBundle\Event\AccountEvents;
use Civix\CoreBundle\Event\BankAccountEvent;
use Civix\CoreBundle\Event\CardEvent;
use Civix\CoreBundle\Event\ChargeEvent;
use Civix\CoreBundle\Event\CustomerEvent;
use Civix\CoreBundle\Event\CustomerEvents;
use Civix\CoreBundle\Event\PollEvents;
use Civix\CoreBundle\Service\Stripe;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StripeSubscriber implements EventSubscriberInterface
{
    /**
     * @var Stripe
     */
    private $stripe;

    public static function getSubscribedEvents()
    {
        return [
            PollEvents::QUESTION_CHARGE => 'chargeToPaymentRequest',
            AccountEvents::PRE_CREATE => 'createStripeAccount',
            AccountEvents::PRE_DELETE => 'deleteStripeAccount',
            AccountEvents::BANK_ACCOUNT_PRE_CREATE => 'createStripeBankAccount',
            AccountEvents::BANK_ACCOUNT_PRE_DELETE => 'deleteStripeBankAccount',
            CustomerEvents::PRE_CREATE => 'createStripeCustomer',
            CustomerEvents::CARD_PRE_CREATE => 'createStripeCard',
            CustomerEvents::CARD_PRE_DELETE => 'deleteStripeCard',
        ];
    }

    public function __construct(Stripe $stripe)
    {
        $this->stripe = $stripe;
    }

    public function chargeToPaymentRequest(ChargeEvent $event)
    {
        $charge = $event->getCharge();
        $stripeCharge = $this->stripe->chargeToPaymentRequest($charge);
        $this->updateStripeData($charge, $stripeCharge);
    }

    public function createStripeAccount(AccountEvent $event)
    {
        $account = $event->getAccount();

        $response = $this->stripe->createAccount($account->getRoot());
        $account
            ->setStripeId($response->id)
            ->setSecretKey($response->keys->secret)
            ->setPublishableKey($response->keys->publishable)
        ;
    }

    public function deleteStripeAccount(AccountEvent $event)
    {
        $account = $event->getAccount();

        $this->stripe->deleteAccount($account);
    }

    public function createStripeBankAccount(BankAccountEvent $event)
    {
        $account = $event->getAccount();
        $bankAccount = $event->getBankAccount();

        $this->stripe->addBankAccount($account, $bankAccount);
        $account->updateBankAccounts($this->stripe->getBankAccounts($account)->data);
    }

    public function deleteStripeBankAccount(BankAccountEvent $event)
    {
        $account = $event->getAccount();
        $bankAccount = $event->getBankAccount();

        $this->stripe->removeBankAccount($account, $bankAccount);
        $account->updateBankAccounts($this->stripe->getBankAccounts($account)->data);
    }

    public function createStripeCustomer(CustomerEvent $event)
    {
        $customer = $event->getCustomer();

        $response = $this->stripe->createCustomer($customer->getUser());
        $customer->setStripeId($response->id);
    }

    public function createStripeCard(CardEvent $event)
    {
        $customer = $event->getCustomer();
        $card = $event->getCard();

        $this->stripe->addCard($customer, $card);
        $customer->updateCards($this->stripe->getCards($customer)->data);
    }

    public function deleteStripeCard(CardEvent $event)
    {
        $customer = $event->getCustomer();
        $card = $event->getCard();

        $this->stripe->removeCard($customer, $card);
        $customer->updateCards($this->stripe->getCards($customer)->data);
    }

    private function updateStripeData(Charge $charge, \Stripe\Charge $sc)
    {
        $charge->setStripeId($sc->id);
        $charge->setStatus($sc->status);
        $charge->setAmount($sc->amount);
        $charge->setCurrency($sc->currency);
        $charge->setApplicationFee($sc->application_fee);
        $charge->setReceiptNumber($sc->receipt_number);
        $charge->setCreated($sc->created);
    }
}