<?php
namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Stripe\Charge;
use Civix\CoreBundle\Event\ChargeEvent;
use Civix\CoreBundle\Event\Poll\AnswerEvent;
use Civix\CoreBundle\Event\Poll\QuestionEvent;
use Civix\CoreBundle\Event\PollEvents;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PollManager
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EntityManager $em, EventDispatcherInterface $dispatcher)
    {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Question $question
     * @return Question
     */
    public function publish(Question $question)
    {
        $question->setPublishedAt(new \DateTime());
        $this->em->persist($question);
        $this->em->flush();

        $event = new QuestionEvent($question);
        $this->dispatcher->dispatch(PollEvents::QUESTION_PUBLISHED, $event);

        return $question;
    }

    public function savePoll(Question $poll)
    {
        $isNew = !$poll->getId();
        $event = new QuestionEvent($poll);

        $this->dispatcher->dispatch(PollEvents::QUESTION_PRE_CREATE, $event);

        $this->em->persist($poll);
        $this->em->flush();

        if ($isNew) {
            $this->dispatcher->dispatch(PollEvents::QUESTION_CREATE, $event);
        }

        return $poll;
    }

    /**
     * @param Question $question
     * @param Answer $answer
     * @return Answer
     */
    public function addAnswer(Question $question, Answer $answer)
    {
        $question->addAnswer($answer);
        $this->em->persist($answer);

        if ($question instanceof Question\PaymentRequest
            && !$question->getIsCrowdfunding()
            && $answer->getCurrentPaymentAmount()
        ) {
            $this->chargeToPaymentRequest($question, $answer);
        }
        $this->em->flush();

        $event = new AnswerEvent($answer);
        $this->dispatcher->dispatch(PollEvents::QUESTION_ANSWER, $event);

        return $answer;
    }

    public function chargeToPaymentRequest(Question $question, Answer $answer)
    {
        $user = $answer->getUser();
        $customer = $user->getStripeCustomer();

        if (!$customer->getId()) {
            throw new \RuntimeException(ucfirst($user->getType())." doesn't have an account in stripe");
        }

        $account = $question->getOwner()->getStripeAccount();

        if (!$account->getId()) {
            throw new \RuntimeException(ucfirst($question->getOwner()->getType())." doesn't have an account in stripe");
        }

        $charge = new Charge($customer, $account, $question);
        $charge->setAmount($answer->getCurrentPaymentAmount() * 100);
        $this->em->persist($charge);

        $event = new ChargeEvent($charge);
        $this->dispatcher->dispatch(PollEvents::QUESTION_CHARGE, $event);
    }
}