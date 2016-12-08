<?php

namespace Civix\ApiBundle\Controller;

use Civix\CoreBundle\Entity\UserPetition\Signature;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Civix\CoreBundle\Entity\Poll\Question\Petition;
use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Micropetitions\Petition as MicroPetition;
use Civix\CoreBundle\Entity\Stripe\Charge;
use Civix\CoreBundle\Entity\Stripe\Customer;

class AnswersController extends BaseController
{
    /**
     * Unsign petition answer.
     *
     * @Route(
     *      "/petition/{id}/answers/{answerId}",
     *      requirements={"entityId"="\d+", "answerId"="\d+"},
     *      name="api_petition_answer_unsign"
     * )
     * @Method("DELETE")
     * @ParamConverter("answer", options={"mapping": {"answerId": "id"}})
     * @ApiDoc(
     *     section="Polls",
     *     description="Unsign answer",
     *     filters={
     *         {"name"="entityId", "dataType"="integer"},
     *         {"name"="answerId", "dataType"="integer"},
     *     },
     *     statusCodes={
     *         200="Answer successfully removed",
     *         400="Bad Request",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param Petition $petition
     * @param $answerId
     * @return Response
     */
    public function unsignPetitionAnswerAction(Petition $petition, $answerId)
    {
        $em = $this->getDoctrine()->getManager();

        $answer = $em->getRepository('CivixCoreBundle:Poll\Answer')->findOneBy(array(
            'user' => $this->getUser(),
            'question' => $petition,
        ));

        if (!$answer || $answerId != $answer->getOption()->getId()) {
            throw $this->createNotFoundException();
        }
        $em->remove($answer);
        $em->flush();
        $response = new Response(json_encode(array('status' => 'ok')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Unsign petition answer.
     * Deprecated, use `DELETE /api/v2/micro-petitions/{id}/answer` instead.
     *
     * @Route(
     *      "/micro-petitions/{id}/answers/{answerId}",
     *      requirements={"entityId"="\d+", "answerId"="\d+"},
     *      name="api_micro_petition_answer_unsign"
     * )
     * @Method("DELETE")
     *
     * @ApiDoc(
     *     section="User Petitions",
     *     description="Unsign answer",
     *     filters={
     *         {"name"="entityId", "dataType"="integer"},
     *         {"name"="answerId", "dataType"="integer"},
     *     },
     *     statusCodes={
     *         200="Answer successfully removed",
     *         400="Bad Request",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     * @param MicroPetition $microPetition
     * @return Response
     */
    public function unsignMicroPetitionsAnswerAction(MicroPetition $microPetition)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var Signature $answer */
        $answer = $em->getRepository('CivixCoreBundle:Micropetitions\Answer')->findOneBy(array(
            'user' => $this->getUser(),
            'petition' => $microPetition,
        ));

        if (!$answer) {
            throw $this->createNotFoundException();
        }

        $micropetitionService = $this->get('civix_core.poll.micropetition_manager');
        $micropetitionService->unsignPetition($answer);

        $response = new Response(json_encode(array('status' => 'ok')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/answers/payment-history/{id}")
     * @Method("GET")
     *
     * @deprecated
     */
    public function paymentHistory()
    {
        throw $this->createNotFoundException();
    }

    /**
     * @Route("/answers/{id}/charges/")
     * @Method("GET")
     *
     * @ApiDoc(
     *     section="Polls",
     *     description="Returns charges"
     * )
     *
     * @param Answer $answer
     * @return Response
     */
    public function charges(Answer $answer)
    {
        if ($answer->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException();
        }

        $customer = $this->getUser()->getStripeCustomer();

        if (!$customer) {
            throw $this->createNotFoundException();
        }

        $charge = $this->getDoctrine()
            ->getRepository(Charge::class)
            ->findOneBy([
                'question' => $answer->getQuestion(),
                'fromCustomer' => $customer,
            ])
        ;

        if (!$charge) {
            throw $this->createNotFoundException();
        }

        return $this->createJSONResponse(
            $this->jmsSerialization($charge->toArray(), ['api-answer-private'])
        );
    }
}
