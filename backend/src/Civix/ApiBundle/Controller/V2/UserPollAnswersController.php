<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\CoreBundle\Entity\Poll\Answer;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class UserPollAnswersController
 * @package Civix\ApiBundle\Controller\V2
 *
 * @Route("/user/poll-answers")
 */
class UserPollAnswersController extends FOSRestController
{
    /**
     * List poll answers
     *
     * @Route("")
     * @Method("GET")
     *
     * @QueryParam(name="page", requirements="\d+", default="1")
     * @QueryParam(name="per_page", requirements="(10|20)", default="20")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Polls",
     *     description="List poll answers",
     *     output="Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *     filters={
     *          {"name"="start", "dataType"="datetime", "description"="Start date"}
     *     },
     *     statusCodes={
     *         200="Returns poll answers",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api-answers-list"})
     *
     * @param ParamFetcher $params
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getcAction(ParamFetcher $params)
    {
        $param = new QueryParam();
        $param->name = 'start';
        $param->requirements = new DateTime();
        $params->addParam($param);

        $query = $this->getDoctrine()
            ->getRepository(Answer::class)
            ->getFindByUserAndCriteriaQuery($this->getUser(), $params->all());

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }
}