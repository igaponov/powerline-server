<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Service\FollowerManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

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
     * @var EntityManagerInterface
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

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
     *     description="List the authenticated user's followers",
     *     output = "Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *     statusCodes={
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api-follow", "api-info", "api-followers"})
     *
     * @param ParamFetcher $params
     * 
     * @return PaginationInterface
     */
    public function getFollowersAction(ParamFetcher $params): PaginationInterface
    {
        $query = $this->em->getRepository(UserFollow::class)
            ->getFindByUserQuery($this->getUser());

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }

    /**
     * Profile of a follower of the authenticated user
     *
     * @Route("/{id}")
     * @Method("GET")
     *
     * @ParamConverter("userFollow", options={"mapping" = {"loggedInUser" = "user", "id" = "follower"}}, converter="doctrine.param_converter")
     *
     * @ApiDoc(
     *     authentication = true,
     *     section="Followers",
     *     description="Follower's profile",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\UserFollow",
     *          "groups" = {"api-info", "api-followers"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         404="Follower Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-info", "api-followers"})
     *
     * @param Request $request
     * @param UserFollow $userFollow
     *
     * @return UserFollow
     */
    public function getAction(Request $request, UserFollow $userFollow): UserFollow
    {
        if ($userFollow && $userFollow->isActive()) {
            /** @var View $configuration */
            $configuration = $request->attributes->get('_template');
            $groups = $configuration->getSerializerGroups();
            $groups[] = 'api-full-info';
            $configuration->setSerializerGroups($groups);
        }

        return $userFollow;
    }

    /**
     * Approve a follow request
     *
     * @Route("/{id}", requirements={"id"="\d+"})
     * @Method("PATCH")
     *
     * @ParamConverter("userFollow", options={"mapping" = {"loggedInUser" = "user", "id" = "follower"}}, converter="doctrine.param_converter")
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
     *         404="Follow Request Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param UserFollow $userFollow
     */
    public function patchAction(UserFollow $userFollow): void
    {
        $this->manager->approve($userFollow);
    }

    /**
     * Unapprove a user's follow request
     *
     * @Route("/{id}")
     * @Method("DELETE")
     *
     * @ParamConverter("userFollow", options={"mapping" = {"loggedInUser" = "user", "id" = "follower"}}, converter="doctrine.param_converter")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Followers",
     *     description="Unapprove a user's follow request",
     *     requirements={
     *         {
     *             "name"="id",
     *             "dataType"="integer",
     *             "requirement"="\d+",
     *             "description"="Follower's id"
     *         }
     *     },
     *     statusCodes={
     *         404="Follow Request Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param UserFollow $userFollow
     */
    public function deleteAction(UserFollow $userFollow): void
    {
        $this->manager->unfollow($userFollow);
    }
}
