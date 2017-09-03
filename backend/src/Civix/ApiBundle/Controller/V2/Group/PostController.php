<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Form\Type\PostType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Service\PostManager;
use FOS\RestBundle\Controller\Annotations as REST;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/groups/{group}/posts")
 */
class PostController extends FOSRestController
{
    /**
     * @var PostManager
     * @DI\Inject("civix_core.post_manager")
     */
    private $manager;

    /**
     * Create a user's post in a group
     *
     * @REST\Post("")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Posts",
     *     description="Create a user's post in a group",
     *     input="Civix\ApiBundle\Form\Type\PostType",
     *     statusCodes={
     *         400="Bad Request",
     *         405="Method Not Allowed"
     *     },
     *     responseMap={
     *          201 = {
     *              "class"="Civix\CoreBundle\Entity\Post",
     *              "groups"={"Default"},
     *              "parsers" = {
     *                  "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *              }
     *          }
     *     }
     * )
     *
     * @View(statusCode=201)
     *
     * @param Request $request
     * @param Group $group
     *
     * @return Post|\Symfony\Component\Form\Form
     */
    public function postAction(Request $request, Group $group)
    {
        $form = $this->createForm(PostType::class);
        $form->submit($request->request->all());

        // check limit petition
        if (!$this->manager->checkPostLimitPerMonth($this->getUser(), $group)) {
            $form->addError(new FormError('Your limit of posts per month is reached.'));
        }

        if ($form->isValid()) {
            /** @var Post $post */
            $post = $form->getData();
            $post->setUser($this->getUser());
            $post->setGroup($group);
            $post = $this->manager->savePost($post);

            return $post;
        }

        return $form;
    }

    /**
     * List all the posts from the group.
     *
     * @REST\Get("")
     *
     * @Security("is_granted('view', group)")
     *
     * @QueryParam(name="marked_as_spam", requirements="true|false", description="Filter by spam marks", default=false)
     * @QueryParam(name="user", requirements="\d+", description="Filter by user ID")
     * @QueryParam(name="page", requirements="\d+", default=1)
     * @QueryParam(name="per_page", requirements="(10|20)", default="20")
     * @QueryParam(name="sort", requirements="(createdAt)", default="createdAt")
     * @QueryParam(name="sort_dir", requirements="(ASC|DESC)", default="DESC")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Posts",
     *     description="List all the posts from the group.",
     *     output={
     *          "class" = "array<Civix\CoreBundle\Entity\Post> as paginator",
     *          "groups" = {"Default", "api-info"},
     *          "parsers" = {
     *              "Civix\ApiBundle\Parser\PaginatorParser"
     *          }
     *     },
     *     statusCodes={
     *         403="Access Denied",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "Default", "api-info"})
     *
     * @param ParamFetcher $params
     * @param Group $group
     *
     * @return PaginationInterface
     */
    public function getPostsAction(ParamFetcher $params, Group $group): PaginationInterface
    {
        $query = $this->getDoctrine()->getRepository(Post::class)
            ->getFindByGroupQuery(
                $group,
                $params->all(),
                [
                    $params->get('sort') => $params->get('sort_dir')
                ]
            );

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }
}
