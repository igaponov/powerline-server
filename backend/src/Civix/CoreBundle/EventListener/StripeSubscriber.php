<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Poll\Question\PaymentRequest;
use Civix\CoreBundle\Event\AccountEvent;
use Civix\CoreBundle\Event\AccountEvents;
use Civix\CoreBundle\Event\BankAccountEvent;
use Civix\CoreBundle\Event\Poll\AnswerEvent;
use Civix\CoreBundle\Event\PollEvents;
use Civix\CoreBundle\Service\Stripe;
use Stripe\Account;
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
            PollEvents::QUESTION_ANSWER => 'chargeToPaymentRequest',
            AccountEvents::PRE_CREATE => 'createStripeAccount',
            AccountEvents::BANK_ACCOUNT_PRE_CREATE => 'createStripeBankAccount',
        ];
    }

    public function __construct(Stripe $stripe)
    {
        $this->stripe = $stripe;
    }

    public function chargeToPaymentRequest(AnswerEvent $event)
    {
        $answer = $event->getAnswer();
        $question = $answer->getQuestion();

        if ($question instanceof PaymentRequest && !$question->getIsCrowdfunding() &&
            $answer->getCurrentPaymentAmount()) {
            $this->stripe->chargeToPaymentRequest($answer);
        }
    }

    public function createStripeAccount(AccountEvent $event)
    {
        $account = $event->getAccount();
        /** @var Account $response */
        $response = $this->stripe->createAccount($account->getUser());
        $account
            ->setStripeId($response->id)
            ->setSecretKey($response->keys->secret)
            ->setPublishableKey($response->keys->publishable)
        ;
    }

    public function createStripeBankAccount(BankAccountEvent $event)
    {
        $account = $event->getAccount();
        $bankAccount = $event->getBankAccount();

        $this->stripe->addBankAccount($account, $bankAccount);
        $account->updateBankAccounts($this->stripe->getBankAccounts($account)->data);
    }
}