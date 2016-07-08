<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\Poll\QuestionType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Poll\Question;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for Leader Polls controller
 * 
 * @Route("/groups/{group}/polls")
 */
class PollController extends FOSRestController
{
    /**
     * List all the polls and questions based in the current user type.
     *
     * @Route("")
     * @Method("GET")
     *
     * @SecureParam("group", permission="view")
     *
     * @QueryParam(name="filter", requirements="published|unpublished|publishing|archived", description="Filter by question state")
     * @QueryParam(name="page", requirements="\d+", default=1)
     * @QueryParam(name="per_page", requirements="(10|20)", default="20")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Polls",
     *     description="List all the polls and questions based in the current user type.",
     *     output="Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *     statusCodes={
     *         200="Returns list",
     *         403="Access Denied",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api-poll"})
     *
     * @param ParamFetcher $params
     * @param Group $group
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getcAction(ParamFetcher $params, Group $group)
    {
        /** @var $query \Doctrine\ORM\Query */
        $query = $this->getDoctrine()->getRepository('CivixCoreBundle:Poll\Question')
            ->getFilteredQuestionQuery(
                $params->get('filter'),
                $group,
                Question\Group::class
            );

        $paginator = $this->get('knp_paginator');
        return $paginator->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }

    /**
     * Add poll
     *
     * @Route("")
     * @Method("POST")
     *
     * @SecureParam("group", permission="manage")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Polls",
     *     description="Add poll",
     *     input="Civix\ApiBundle\Form\Type\Poll\QuestionType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Poll\Question",
     *          "groups" = {"api-poll"}
     *     },
     *     statusCodes={
     *         201="Success",
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-poll"})
     *
     * @param Request $request
     * @param Group $group
     *
     * @return Question|\Symfony\Component\Form\Form
     */
    public function postAction(Request $request, Group $group)
    {
        $form = $this->createForm(new QuestionType($group));

        $form->submit($request);
        if ($form->isValid()) {
            $question = $form->getData();
            $question->setUser($group);

            $em = $this->getDoctrine()->getManager();
            $em->persist($question);
            $em->flush();

            return $question;
        }

        return $form;
    }
}
