<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Service\FollowerManager;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/user/followers")
 */
class UserFollowerController extends FOSRestController
{
    /**
     * @var FollowerManager
     * @DI\Inject("civix_core.service.follower_manager")
     */
    private $manager;

    /**
     * List followers of a user
     *
     * @Route("")
     * @Method("GET")
     *
     * @QueryParam(name="page", requirements="\d+", default="1")
     * @QueryParam(name="per_page", requirements="(10|20)", default="20")
     *
     * @ApiDoc(
     *     authentication = true,
     *     resource=true,
     *     section="Followers",
     *     output = "Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *     description="List the authenticated user's followers",
     *     statusCodes={
     *         200="Get followers",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api-follow", "api-info"})
     *
     * @param ParamFetcher $params
     * 
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getcAction(ParamFetcher $params)
    {
        $query = $this->getDoctrine()->getRepository(UserFollow::class)
            ->getFindByUserQuery($this->getUser());

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }

    /**
     * Follow a user
     *
     * @Route("/{id}", requirements={"id"="\d+"})
     * @Method("PUT")
     * 
     * @ParamConverter("user")
     * 
     * @ApiDoc(
     *     authentication=true,
     *     section="Followers",
     *     resource=true,
     *     description="Follow a user",
     *     requirements={
     *         {
     *             "name"="id",
     *             "dataType"="integer",
     *             "requirement"="\d+",
     *             "description"="User id"
     *         }
     *     },
     *     statusCodes={
     *         204="Success",
     *         401="Authorization required",
     *         404="Follower Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param User $user
     */
    public function putAction(User $user)
    {
        $this->manager->follow($user, $this->getUser());
    }

    /**
     * Approve a follow request
     *
     * @Route("/{id}", requirements={"id"="\d+"})
     * @Method("PATCH")
     *
     * @ParamConverter("userFollow", options={"mapping" = {"id" = "user", "loggedInUser" = "follower"}}, converter="doctrine.param_converter")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Followers",
     *     description="Approve a follow request",
     *     requirements={
     *         {
     *             "name"="id",
     *             "dataType"="integer",
     *             "requirement"="\d+",
     *             "description"="User id"
     *         }
     *     },
     *     statusCodes={
     *         204="Success",
     *         401="Authorization required",
     *         404="Follow request Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param UserFollow $userFollow
     */
    public function patchAction(UserFollow $userFollow)
    {
        $this->manager->approve($userFollow);
    }

    /**
     * Unfollow a user
     *
     * @Route("/{id}")
     * @Method("DELETE")
     *
     * @ParamConverter("userFollow", options={"mapping" = {"id" = "user", "loggedInUser" = "follower"}}, converter="doctrine.param_converter")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Followers",
     *     description="Unfollow a user",
     *     requirements={
     *         {
     *             "name"="id",
     *             "dataType"="integer",
     *             "requirement"="\d+",
     *             "description"="User id"
     *         }
     *     },
     *     statusCodes={
     *         204="Success",
     *         401="Authorization required",
     *         404="Follow request Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param UserFollow $userFollow
     */
    public function deleteAction(UserFollow $userFollow)
    {
        $this->manager->unfollow($userFollow);
    }
}
