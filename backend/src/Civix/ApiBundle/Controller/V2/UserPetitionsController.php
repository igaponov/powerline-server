<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Service\User\UserManager;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/user/user-petitions")
 */
class UserPetitionsController extends FOSRestController
{
    /**
     * @var UserManager
     * @DI\Inject("civix_core.user_manager")
     */
    private $manager;

    /**
     * List user group's petitions
     *
     * @Route("")
     * @Method("GET")
     *
     * @QueryParam(name="page", requirements="\d+", default="1")
     * @QueryParam(name="per_page", requirements="(10|20)", default="20")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="User Petitions",
     *     resource=true,
     *     description="List petitions from user's groups",
     *     output="Knp\Component\Pager\Pagination\SlidingPagination",
     *     statusCodes={
     *         401="Unauthorized Request",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param ParamFetcher $params
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getcAction(ParamFetcher $params)
    {
        $query = $this->getDoctrine()->getManager()
            ->getRepository(UserPetition::class)
            ->getFindByUserGroupsQuery($this->getUser());

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }

    /**
     * @Route("/{id}", requirements={"id"="\d+"})
     * @Method("PUT")
     *
     * @SecureParam("petition", permission="subscribe")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="User Petitions",
     *     description="Subscribe to a petition",
     *     requirements={
     *         {"name"="id", "dataType"="integer", "description"="Petition id"}
     *     },
     *     statusCodes={
     *         204="Success",
     *         404="Petition Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param UserPetition $petition
     */
    public function putAction(UserPetition $petition)
    {
        $this->manager->subscribeToPetition($this->getUser(), $petition);
    }

    /**
     * @Route("/{id}", requirements={"id"="\d+"})
     * @Method("DELETE")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="User Petitions",
     *     description="Unsubscribe from a petition",
     *     requirements={
     *         {"name"="id", "dataType"="integer", "description"="Petition id"}
     *     },
     *     statusCodes={
     *         204="Success",
     *         404="Petition Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param UserPetition $petition
     */
    public function deleteAction(UserPetition $petition)
    {
        $this->manager->unsubscribeFromPetition($this->getUser(), $petition);
    }
}
