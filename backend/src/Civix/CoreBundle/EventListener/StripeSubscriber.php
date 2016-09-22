<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Poll\Question\PaymentRequest;
use Civix\CoreBundle\Event\Poll\AnswerEvent;
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
            PollEvents::QUESTION_ANSWER => 'chargeToPaymentRequest',
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
}