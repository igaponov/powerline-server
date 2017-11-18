<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Stripe\Charge;
use Civix\CoreBundle\Event\ChargeEvent;
use Civix\CoreBundle\Event\GroupEvent;
use Civix\CoreBundle\Event\GroupEvents;
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
            GroupEvents::CREATED => 'createAccount',
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

    public function createAccount(GroupEvent $event)
    {
        $this->stripe->createAccount($event->getGroup());
    }

    private function updateStripeData(Charge $charge, \Stripe\Charge $sc)
    {
        $charge->setStripeId($sc->id);
        $charge->setStatus($sc->status);
        $charge->setAmount((int)$sc->amount);
        $charge->setCurrency($sc->currency);
        $charge->setApplicationFee((int)$sc->application_fee);
        $charge->setReceiptNumber($sc->receipt_number);
        $charge->setCreated((int)$sc->created);
    }
}