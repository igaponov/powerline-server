<?php

namespace Civix\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Civix\CoreBundle\Exception\LogicException;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Poll\Question\LeaderEvent;
use Civix\CoreBundle\Entity\Poll\Question\PaymentRequest;

/**
 * @Route("/admin/reports")
 */
class ReportController extends Controller
{
    public function getQuestionClass()
    {
        return 'CivixCoreBundle:Poll\Question\Superuser';
    }

    public function getEventClass()
    {
        throw new LogicException('Superuser can\'t create events');
    }

    public function getPaymentRequestClass()
    {
        throw new LogicException('Superuser can\'t create payment requests');
    }

    /**
     * @Route("", name="civix_front_superuser_report_index")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Reports:questions.html.twig")
     * @param Request $request
     * @return array
     */
    public function indexAction(Request $request)
    {
        return $this->getQuestionList($request, $this->getQuestionClass());
    }

    /**
     * @Route("/{id}",  requirements={"id"="\d+"})
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Reports:questionDetails.html.twig")
     * @param Question $question
     * @return array
     */
    public function questionAction(Question $question)
    {
        return $this->getQuestionDetails($question, $this->getQuestionClass());
    }

    /**
     * @Route("/events")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Reports:events.html.twig")
     * @param Request $request
     * @return array
     */
    public function eventsAction(Request $request)
    {
        return $this->getQuestionList($request, $this->getEventClass());
    }

    /**
     * @Route("/events/{id}",  requirements={"id"="\d+"})
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Reports:eventDetails.html.twig")
     * @param LeaderEvent $event
     * @return array
     */
    public function eventAction(LeaderEvent $event)
    {
        return $this->getQuestionDetails($event, $this->getEventClass());
    }

    /**
     * @Route("/payment-requests")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Reports:payments.html.twig")
     * @param Request $request
     * @return array
     */
    public function paymentsAction(Request $request)
    {
        return $this->getQuestionList($request, $this->getPaymentRequestClass());
    }

    /**
     * @Route("/payment-requests/{id}",  requirements={"id"="\d+"})
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Reports:paymentDetails.html.twig")
     * @param PaymentRequest $payment
     * @return array
     */
    public function paymentAction(PaymentRequest $payment)
    {
        return $this->getQuestionDetails($payment, $this->getPaymentRequestClass());
    }

    private function getQuestionList(Request $request, $class)
    {
        try {
            $query = $this->getDoctrine()->getRepository('CivixCoreBundle:Poll\Question')
                ->getPublishedQuestionQuery($this->getUser(), $class);
        } catch (LogicException $e) {
            throw $this->createNotFoundException();
        }

        $pagination = $this->get('knp_paginator')->paginate(
            $query,
            $request->get('page', 1),
            10
        );

        return [
            'pagination' => $pagination,
            'token' => $this->getToken(),
        ];
    }

    private function getQuestionDetails(Question $question, $class)
    {
        try {
            $questionDetails = $this->getDoctrine()->getRepository('CivixCoreBundle:Poll\Question')
                ->getPublishedQuestionWithAnswers($question->getId(), $class);
        } catch (LogicException $e) {
            throw $this->createNotFoundException();
        }

        if (!$questionDetails) {
            throw $this->createNotFoundException();
        }
        $statistics = $question->getStatistic(['#7ac768', '#ba3830', '#4fb0f3', '#dbfa08', '#08fac4']);

        return [
            'statistics' => $statistics,
            'question' => $questionDetails,
        ];
    }

    /**
     * @return string
     */
    protected function getToken()
    {
        return $this->get('security.csrf.token_manager')->getToken('question');
    }
}
