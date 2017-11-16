<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\Poll\AnswerType;
use Civix\ApiBundle\Form\Type\Poll\OptionType;
use Civix\ApiBundle\Form\Type\Poll\QuestionType;
use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Poll\Option;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\TempFile;
use Civix\CoreBundle\QueryFunction\PollResponsesQuery;
use Civix\CoreBundle\Service\PollManager;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class for Leader Polls controller
 *
 * @Route("/polls")
 */
class PollController extends FOSRestController
{
    /**
     * @var ValidatorInterface
     * @DI\Inject("validator")
     */
    private $validator;

    /**
     * @var PollManager
     * @DI\Inject("civix_core.poll_manager")
     */
    private $manager;

    /**
     * @var EntityManager
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

    /**
     * Return poll
     *
     * @Route("/{id}")
     * @Method("GET")
     *
     * @ParamConverter("question", options={"repository_method" = "findOneWithUserAnswerAndGroups", "mapping" = {"id" = "id", "loggedInUser" = "user"}}, converter="doctrine.param_converter")
     * @SecureParam("question", permission="view")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Polls",
     *     description="Return poll",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Poll\Question",
     *          "groups" = {"api-poll"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-poll"})
     *
     * @param Question $question
     *
     * @return Question
     */
    public function getAction(Question $question): Question
    {
        return $question;
    }

    /**
     * Update poll
     *
     * @Route("/{id}")
     * @Method("PUT")
     *
     * @SecureParam("question", permission="content")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Polls",
     *     description="Update poll",
     *     input="Civix\ApiBundle\Form\Type\Poll\QuestionType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Poll\Question",
     *          "groups" = {"api-poll"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         204="Success",
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Poll Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-poll"})
     *
     * @param Request $request
     * @param Question $question
     *
     * @return Question|\Symfony\Component\Form\Form
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putAction(Request $request, Question $question)
    {
        $form = $this->createForm(QuestionType::class, $question, ['validation_groups' => 'update', 'root_model' => $question->getOwner()]);
        $form->submit($request->request->all(), false);

        if ($form->isValid()) {
            $this->em->persist($question);
            $this->em->flush();

            return $question;
        }

        return $form;
    }

    /**
     * Publish poll
     *
     * @Route("/{id}")
     * @Method("PATCH")
     *
     * @SecureParam("question", permission="content")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Polls",
     *     description="Publish poll",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Poll\Question",
     *          "groups" = {"api-poll"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Poll Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-poll"})
     *
     * @param Question $question
     *
     * @return Question|ConstraintViolationListInterface
     */
    public function patchAction(Question $question)
    {
        $violations = $this->validator->validate($question, null, ['publish']);

        if (!$violations->count()) {
            return $this->manager->publish($question);
        }

        return $violations;
    }

    /**
     * Delete poll
     *
     * @Route("/{id}")
     * @Method("DELETE")
     *
     * @SecureParam("question", permission="content")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Polls",
     *     description="Delete poll",
     *     statusCodes={
     *         204="Success",
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Question Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Question $question
     *
     * @return mixed
     */
    public function deleteAction(Question $question)
    {
        $violations = $this->get('validator')->validate($question, null, ['update']);
        if (!$violations->count()) {
            $this->em->remove($question);
            $this->em->flush();

            return null;
        }
        return $violations;
    }

    /**
     * Add poll's option
     *
     * @Route("/{id}/options")
     * @Method("POST")
     *
     * @SecureParam("question", permission="content")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Polls",
     *     description="Add poll's option",
     *     input="Civix\ApiBundle\Form\Type\Poll\OptionType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Poll\Option",
     *          "groups" = {"api-poll"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         200="Success",
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Poll Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-poll"})
     *
     * @param Request $request
     * @param Question $question
     *
     * @return \Civix\CoreBundle\Entity\Poll\Option|\Symfony\Component\Form\Form
     */
    public function postOptionAction(Request $request, Question $question)
    {
        $form = $this->createForm(OptionType::class);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            /** @var Option $option */
            $option = $form->getData();
            $option->setQuestion($question);

            $this->em->persist($option);
            $this->em->flush();

            return $option;
        }

        return $form;
    }

    /**
     * List all the answers for a given question.
     *
     * The current user only can list the answers if it is the question user creator.
     *
     * @Route("/{id}/answers")
     * @Method("GET")
     *
     * @ParamConverter("question", options={"repository_method" = "findWithGroupAndRepresentative"})
     *
     * @SecureParam("question", permission="view")
     *
     * @QueryParam(name="page", requirements="\d+", default=1)
     * @QueryParam(name="per_page", requirements="(10|15|20)", default="20")
     * @QueryParam(name="following", requirements="1|0|", default=null)
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Polls",
     *     description="List the answers for a given question.",
     *     output={
     *          "class" = "array<Civix\CoreBundle\Entity\Poll\Answer> as paginator",
     *          "groups" = {"api-leader-answers"},
     *          "parsers" = {
     *              "Civix\ApiBundle\Parser\PaginatorParser"
     *          }
     *     },
     *     statusCodes={
     *         200="Returns list",
     *         403="Access Denied",
     *         404="Question Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api-leader-answers"})
     *
     * @param ParamFetcher $params
     * @param Question $question
     *
     * @return PaginationInterface
     */
    public function getAnswersAction(ParamFetcher $params, Question $question): PaginationInterface
    {
        $query = $this->em->getRepository('CivixCoreBundle:Poll\Answer')
            ->getAnswersByQuestion(
                $question,
                $params->get('following') !== null ? $this->getUser() : null,
                !$params->get('following')
            );

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page'),
            ['distinct' => false]
        );
    }

    /**
     * Add a new answer.
     *
     * @Route("/{id}/answers/{option}", requirements={"id"="\d+"})
     * @Method("PUT")
     *
     * @ParamConverter("option", options={"mapping" = {"id" = "question", "option" = "id"}})
     * @ParamConverter("answer", options={"mapping" = {"loggedInUser" = "user", "id" = "question"}}, converter="doctrine.param_converter")
     *
     * @SecureParam("question", permission="answer")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Polls",
     *     description="Add a new answer.",
     *     input="Civix\ApiBundle\Form\Type\Poll\AnswerType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Poll\Answer",
     *          "groups" = {"api-answer"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Question or Option Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-answer"})
     *
     * @param Request $request
     * @param Question $question
     * @param Option $option
     * @param Answer $answer
     *
     * @return Answer|Form
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putAnswersAction(Request $request, Question $question, Option $option, Answer $answer = null)
    {
        if ($answer === null) {
            $answer = new Answer();
            $answer->setUser($this->getUser());
            $question->addAnswer($answer);
        } elseif ($question instanceof Question\PaymentRequest && $question->isCrowdfundingDeadline()) {
            throw new BadRequestHttpException("Payment can't be changed for a crowdfunding after the deadline.");
        }
        $answer->setOption($option);

        $form = $this->createForm(AnswerType::class, $answer, ['validation_groups' => 'api-poll']);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            return $this->manager->saveAnswer($question, $answer);
        }

        return $form;
    }

    /**
     * List all the responses for a given question.
     *
     * @Route("/{id}/responses")
     * @Method("GET")
     *
     * @ParamConverter("question", options={"repository_method" = "getGroupQuestion"})
     * @SecureParam("question", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Polls",
     *     description="List the responses for a given question.",
     *     output="array",
     *     statusCodes={
     *         200="Returns list",
     *         403="Access Denied",
     *         404="Question Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Question $question
     *
     * @return array
     */
    public function getResponsesAction(Question $question): array
    {
        $query = new PollResponsesQuery($this->em);

        return $query($question);
    }

    /**
     * Return link to a file with all the responses for a given question.
     *
     * @Route("/{id}/responses-link")
     * @Method("GET")
     *
     * @ParamConverter("question", options={"repository_method" = "getGroupQuestion"})
     * @SecureParam("question", permission="edit")
     *
     * @ApiDoc(
     *     authentication = true,
     *     section="Polls",
     *     description="Return link to a file with all the responses for a given question.",
     *     output="\Civix\CoreBundle\Entity\TempFile",
     *     statusCodes={
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Question $question
     *
     * @return TempFile
     */
    public function getResponsesLinkAction(Question $question): TempFile
    {
        $result = $this->getResponsesAction($question);
        $file = new TempFile(
            serialize($result),
            new \DateTime('+2 minutes'),
            'text/csv'
        );
        $this->em->persist($file);
        $this->em->flush();

        return $file;
    }
}