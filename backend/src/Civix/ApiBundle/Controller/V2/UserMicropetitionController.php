<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Micropetitions\Petition;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class MicropetitionController
 * @package Civix\ApiBundle\Controller\V2
 * 
 * @Route("/user/micro-petitions")
 */
class UserMicropetitionController extends FOSRestController
{
    /**
     * List user group's micropetitions
     *
     * @Route("")
     * @Method("GET")
     *
     * @QueryParam(name="page", requirements="\d+", default="1")
     * @QueryParam(name="per_page", requirements="(10|20)", default="20")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Micropetitions",
     *     resource=true,
     *     description="List micropetitions from user's groups",
     *     output="Knp\Component\Pager\Pagination\SlidingPagination",
     *     statusCodes={
     *         200="Returns list micropetitions",
     *         401="Unauthorized Request",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api-petitions-list"})
     *
     * @param ParamFetcher $params
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getcAction(ParamFetcher $params)
    {
        $query = $this->getDoctrine()->getManager()
            ->getRepository(Petition::class)
            ->getFindByUserGroupsQuery($this->getUser());

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }
}
