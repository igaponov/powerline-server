<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\Poll\OptionType;
use Civix\ApiBundle\Form\Type\Poll\QuestionType;
use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Poll\Comment;
use Civix\CoreBundle\Entity\Poll\Option;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Service\PollManager;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator;

/**
 * Class for Leader Polls controller
 *
 * @Route("/polls")
 */
class PollController extends FOSRestController
{
    /**
     * @var Validator
     * @DI\Inject("validator")
     */
    private $validator;

    /**
     * @var PollManager
     * @DI\Inject("civix_core.poll_manager")
     */
    private $manager;

    /**
     * Return poll
     *
     * @Route("/{id}")
     * @Method("GET")
     *
     * @SecureParam("question", permission="view")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Polls",
     *     description="Return poll",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Poll\Question",
     *          "groups" = {"api-poll"}
     *     },
     *     statusCodes={
     *         200="Returns poll",
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
    public function getAction(Question $question)
    {
        return $question;
    }

    /**
     * Update poll
     *
     * @Route("/{id}")
     * @Method("PUT")
     *
     * @SecureParam("question", permission="manage")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Polls",
     *     description="Update poll",
     *     input="Civix\ApiBundle\Form\Type\Poll\QuestionType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Poll\Question",
     *          "groups" = {"api-poll"}
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
     */
    public function putAction(Request $request, Question $question)
    {
        $form = $this->createForm(new QuestionType($question->getOwner()), $question, ['validation_groups' => 'update']);

        $form->submit($request, false);
        if ($form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($question);
            $entityManager->flush();

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
     * @SecureParam("question", permission="manage")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Polls",
     *     description="Publish poll",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Poll\Question",
     *          "groups" = {"api-poll"}
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
     * @return Question|\Symfony\Component\Form\Form
     */
    public function patchAction(Question $question)
    {
        $violations = $this->validator->validate($question, ['publish']);

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
     * @SecureParam("question", permission="manage")
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
        $violations = $this->get('validator')->validate($question, ['update']);
        if (!$violations->count()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($question);
            $entityManager->flush();

            return null;
        } else {
            return $violations;
        }
    }

    /**
     * Add poll's option
     *
     * @Route("/{id}/options")
     * @Method("POST")
     *
     * @SecureParam("question", permission="manage")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Polls",
     *     description="Add poll's option",
     *     input="Civix\ApiBundle\Form\Type\Poll\OptionType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Poll\Option",
     *          "groups" = {"api-poll"}
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
        $form = $this->createForm(new OptionType());

        $form->submit($request);
        if ($form->isValid()) {
            /** @var Option $option */
            $option = $form->getData();
            $option->setQuestion($question);

            $em = $this->getDoctrine()->getManager();
            $em->persist($option);
            $em->flush();

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
     * @SecureParam("question", permission="view")
     *
     * @QueryParam(name="page", requirements="\d+", default=1)
     * @QueryParam(name="per_page", requirements="(10|20)", default="20")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Polls",
     *     description="List the answers for a given question.",
     *     output="Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *     statusCodes={
     *         200="Returns list",
     *         403="Access Denied",
     *         404="Question Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api-poll"})
     *
     * @param ParamFetcher $params
     * @param Question $question
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getAnswersAction(ParamFetcher $params, Question $question)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $query = $entityManager->getRepository('CivixCoreBundle:Poll\Answer')
            ->getAnswersByQuestion($question);

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }
}