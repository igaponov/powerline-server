<?php

namespace Civix\ApiBundle\Controller\Leader;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\Poll\QuestionType;
use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Poll\Comment;
use Civix\CoreBundle\Entity\Poll\Option;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Service\PollClassNameFactory;
use Civix\ApiBundle\Form\Type\Poll\OptionType;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Util\Codes;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for Leader Polls controller
 * 
 * @Route("/polls")
 */
class PollController extends FOSRestController
{
    /**
     * List all the polls and questions based in the current user type.
     *
     * @Route("", name="civix_get_polls")
     * @Method("GET")
     * @QueryParam(name="type", requirements="group|representative")
     * @QueryParam(name="filter", requirements="published|unpublished|publishing|archived")
     * @QueryParam(name="page", requirements="\d+", default=1)
     *
     * @ApiDoc(
     *     resource=true,
     *     section="Leader Content",
     *     description="List all the polls and questions based in the current user type.",
     *     output="Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *     statusCodes={
     *         200="Returns list",
     *         400="Bad Request",
     *         401="Authorization required",
     *         404="Question not found",
     *         405="Method Not Allowed"
     *     }
     * )
     * 
     * @View(serializerGroups={"paginator", "api-poll"})
     *
     * @param ParamFetcher $params
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getcAction(ParamFetcher $params)
    {
    	$repositoryType = $params->get('type');
    	$currentUser = $this->getUser();
    	$entityClass = PollClassNameFactory::getEntityClass($repositoryType, $currentUser->getType());

        /** @var $query \Doctrine\ORM\Query */
        $query = $this->getDoctrine()->getRepository('CivixCoreBundle:Poll\Question')
            ->getFilteredQuestionQuery(
                $params->get('filter'),
                $this->getUser(),
                $entityClass
            );

        $paginator = $this->get('knp_paginator');
        return $paginator->paginate(
            $query,
            $params->get('page'),
            20
        );
    }

    /**
     * Return poll
     *
     * @Route("/{id}", name="civix_get_poll")
     * @Method("GET")
     * @SecureParam("question", permission="view")
     *
     * @ParamConverter("question")
     *
     * @ApiDoc(
     *     section="Leader Content",
     *     description="Return poll",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Poll\Question",
     *          "groups" = {"api-poll"}
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
     * Add poll
     *
     * @Route("", name="civix_post_poll")
     * @Method("POST")
     *
     * @ApiDoc(
     *     section="Leader Content",
     *     description="Add poll",
     *     input="Civix\ApiBundle\Form\Type\Poll\QuestionType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Poll\Question",
     *          "groups" = {"api-poll"}
     *     }
     * )
     *
     * @View(serializerGroups={"api-poll"})
     *
     * @param Request $request
     *
     * @return \Civix\CoreBundle\Entity\Poll\Question|\Symfony\Component\Form\Form
     */
    public function postAction(Request $request)
    {
        $form = $this->createForm(new QuestionType($this->getUser()));

        $form->submit($request);
        if ($form->isValid()) {
            $question = $form->getData();
            $question->setUser($this->getUser());

            $em = $this->getDoctrine()->getManager();
            $em->persist($question);
            $em->flush();

            return $question;
        }

        return $form;
    }

    /**
     * Update poll
     *
     * @Route("/{id}", name="civix_put_poll")
     * @Method("PUT")
     * @SecureParam("question", permission="edit")
     *
     * @ParamConverter("question")
     *
     * @ApiDoc(
     *     section="Leader Content",
     *     description="Update poll",
     *     input="Civix\ApiBundle\Form\Type\Poll\QuestionType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Poll\Question",
     *          "groups" = {"api-poll"}
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
        $form = $this->createForm(new QuestionType($this->getUser()), $question);

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
     * Delete poll
     *
     * @Route("/{id}", name="civix_delete_poll")
     * @Method("DELETE")
     * @SecureParam("question", permission="delete")
     *
     * @ParamConverter("question")
     *
     * @ApiDoc(
     *     section="Leader Content",
     *     description="Delete poll",
     *     statusCodes={
     *         204="Question is deleted",
     *         400="Bad Request",
     *         401="Authorization required",
     *         404="Question not found",
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
        if ($question->getPublishedAt() === null) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($question);
            $entityManager->flush();

            return null;
        } else {
            return $this->view('Can not delete published question', Codes::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Add poll's option
     *
     * @Route("/{id}/options", name="civix_post_poll_option")
     * @Method("POST")
     * @SecureParam("question", permission="edit")
     *
     * @ParamConverter("question")
     *
     * @ApiDoc(
     *     section="Leader Content",
     *     description="Add poll's option",
     *     input="Civix\ApiBundle\Form\Type\Poll\OptionType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Poll\Option",
     *          "groups" = {"api-poll"}
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
     * @Route("/{id}/answers", name="civix_get_poll_answers")
     * @Method("GET")
     * @QueryParam(name="page", requirements="\d+", default=1)
     *
     * @ApiDoc(
     *     resource=true,
     *     section="Leader Content",
     *     description="List the answers for a given question.",
     *     output="Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *     statusCodes={
     *         200="Returns list",
     *         400="Bad Request",
     *         401="Authorization required",
     *         404="Question not found",
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
            20
        );
    }

    /**
     * List all the comments for a given question.
     *
     * The current user only can list the comments if it is the question user creator.
     *
     * @Route("/{id}/comments", name="civix_get_poll_comments")
     * @Method("GET")
     * @QueryParam(name="page", requirements="\d+", default=1)
     *
     * @ApiDoc(
     *     resource=true,
     *     section="Leader Content",
     *     description="List the comments for a given question.",
     *     output="Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *     statusCodes={
     *         200="Returns list",
     *         400="Bad Request",
     *         401="Authorization required",
     *         404="Question not found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api-comments"})
     *
     * @param ParamFetcher $params
     * @param Question $question
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getCommentsAction(ParamFetcher $params, Question $question)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $query = $entityManager->getRepository('CivixCoreBundle:Poll\Comment')
            ->getCommentsByQuestion($question);
        
        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            20
        );
    }
}
