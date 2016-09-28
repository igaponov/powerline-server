<?php

namespace Civix\ApiBundle\Controller\V2;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Civix\CoreBundle\Entity\SocialActivity;

/**
 * @Route("/user/social-activities")
 */
class UserSocialActivityController extends FOSRestController
{
    /**
     * Returns current user's social activities.
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
     *     section="Social Activity",
     *     description="eturns current user's social activities.",
     *     output="Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *     filters={
     *          {"name" = "following", "dataType" = "boolean", "default" = false, "description" = "Filter social activities of following users"}
     *     },
     *     statusCodes={
     *         200="Returns list",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api-activities"})
     *
     * @param ParamFetcher $params
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getcAction(ParamFetcher $params)
    {
        $param = new QueryParam();
        $param->name = 'following';
        $params->addParam($param);
        $query = $this->getDoctrine()
            ->getRepository(SocialActivity::class)
            ->getFilteredByFollowingAndRecipientQuery($this->getUser(), $params->get('following'));
        
        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }
}
