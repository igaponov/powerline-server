<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Service\User\UserManager;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/user/posts")
 */
class UserPostController extends FOSRestController
{
    /**
     * @var UserManager
     * @DI\Inject("civix_core.user_manager")
     */
    private $manager;

    /**
     * List user group's posts
     *
     * @Route("")
     * @Method("GET")
     *
     * @QueryParam(name="page", requirements="\d+", default="1")
     * @QueryParam(name="per_page", requirements="(10|20)", default="20")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Posts",
     *     resource=true,
     *     description="List posts from user's groups",
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
            ->getRepository(Post::class)
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
     * @SecureParam("post", permission="subscribe")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Posts",
     *     description="Subscribe to a post",
     *     requirements={
     *         {"name"="id", "dataType"="integer", "description"="Post id"}
     *     },
     *     statusCodes={
     *         204="Success",
     *         404="Micropetition Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Post $post
     */
    public function putAction(Post $post)
    {
        $this->manager->subscribeToPost($this->getUser(), $post);
    }

    /**
     * @Route("/{id}", requirements={"id"="\d+"})
     * @Method("DELETE")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Posts",
     *     description="Unsubscribe from post",
     *     requirements={
     *         {"name"="id", "dataType"="integer", "description"="Post id"}
     *     },
     *     statusCodes={
     *         204="Success",
     *         404="Micropetition Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Post $post
     */
    public function deleteAction(Post $post)
    {
        $this->manager->unsubscribeFromPost($this->getUser(), $post);
    }
}
