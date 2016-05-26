<?php

namespace Civix\ApiBundle\Controller\V2;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class AnnouncementController
 * @package Civix\ApiBundle\Controller\V2
 *
 * @Route("/announcements")
 */
class AnnouncementController extends FOSRestController
{
    /**
     * Return a user's list of announcements
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
     *     section="Announcement",
     *     description="Return a user's list of announcements",
     *     output = {
     *          "class" = "Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *          "groups" = {"paginator", "api"}
     *     },
     *     filters={
                {
     *              "name" = "start", 
     *              "dataType" = "datetime", 
     *              "description" = "Start date", 
     *              "default" = "-1 day"
     *          }
     *     },
     *     statusCodes={
     *         200="Returns announcements",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api"})
     *
     * @param ParamFetcher $params
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getcAction(ParamFetcher $params)
    {
        $param = new QueryParam();
        $param->name = 'start';
        $param->requirements = new Assert\DateTime();
        $param->default = '-1 day';
        $params->addParam($param);

        $start = new \DateTime($params->get('start'));

        $query = $this->getDoctrine()->getRepository('CivixCoreBundle:Announcement')
            ->getByUserQuery($this->getUser(), $start);

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }
}
