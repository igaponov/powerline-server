<?php

namespace Civix\ApiBundle\Controller\V2;

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
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/user/followings")
 */
class UserFollowingController extends FOSRestController
{
    /**
     * @var FollowerManager
     * @DI\Inject("civix_core.service.follower_manager")
     */
    private $manager;

    /**
     * List users followed by the authenticated user
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
     *     description="List the authenticated user's followed users",
     *     output = "Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *     statusCodes={
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api-follow", "api-info", "api-following"})
     *
     * @param ParamFetcher $params
     * 
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getcAction(ParamFetcher $params)
    {
        $query = $this->getDoctrine()->getRepository(UserFollow::class)
            ->getFindByFollowerQuery($this->getUser());

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }

    /**
     * Profile of a followed user
     *
     * @Route("/{id}")
     * @Method("GET")
     *
     * @ParamConverter("userFollow", options={"mapping" = {"id" = "user", "loggedInUser" = "follower"}}, converter="doctrine.param_converter")
     *
     * @ApiDoc(
     *     authentication = true,
     *     section="Followers",
     *     description="Authenticated user's profile",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\User",
     *          "groups" = {"api-info", "api-following"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-info", "api-following"})
     *
     * @param Request $request
     * @param UserFollow $userFollow
     *
     * @return UserFollow
     */
    public function getAction(Request $request, UserFollow $userFollow)
    {
        if ($userFollow && $userFollow->isActive()) {
            /** @var View $configuration */
            $configuration = $request->attributes->get('_view');
            $groups = $configuration->getSerializerGroups();
            $groups[] = 'api-full-info';
            $configuration->setSerializerGroups($groups);
        }

        return $userFollow;
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
     *         404="User Not Found",
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
     *         404="Follow Request Not Found",
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
