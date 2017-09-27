<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\PostType;
use Civix\ApiBundle\Form\Type\VoteType;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\Post\Vote;
use Civix\CoreBundle\QueryFunction\PostResponsesQuery;
use Civix\CoreBundle\QueryFunction\PostVoteAnalyticsQuery;
use Civix\CoreBundle\Service\PostManager;
use Doctrine\ORM\EntityManagerInterface;
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
 * @Route("/posts")
 */
class PostController extends FOSRestController
{
    /**
     * @var PostManager
     * @DI\Inject("civix_core.post_manager")
     */
    private $manager;

    /**
     * @var EntityManagerInterface
     * @DI\Inject("doctrine.orm.default_entity_manager")
     */
    private $em;

    /**
     * List posts
     *
     * @Route("")
     * @Method("GET")
     *
     * @QueryParam(name="tag", requirements=".+", description="Filter by hash tag")
     * @QueryParam(name="page", requirements="\d+", default="1")
     * @QueryParam(name="per_page", requirements="(10|20)", default="20")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Posts",
     *     description="List posts",
     *     output={
     *          "class" = "array<Civix\CoreBundle\Entity\Post> as paginator",
     *          "groups" = {"Default", "post-votes"},
     *          "parsers" = {
     *              "Civix\ApiBundle\Parser\PaginatorParser"
     *          }
     *     },
     *     filters={
     *          {"name"="start", "dataType"="datetime", "description"="Start date"}
     *     },
     *     statusCodes={
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"Default", "post-votes"})
     *
     * @param ParamFetcher $params
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getPostsAction(ParamFetcher $params)
    {
        $query = $this->getDoctrine()
            ->getRepository(Post::class)
            ->getFindWithVotesQuery($this->getUser(), $params->all());

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }

    /**
     * Get a single post
     *
     * @Route("/{id}")
     * @Method("GET")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Posts",
     *     description="Get a single post",
     *     output={
     *         "class"="Civix\CoreBundle\Entity\Post",
     *         "groups"={"Default"},
     *         "parsers" = {
     *             "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *         }
     *     },
     *     statusCodes={
     *         404="Post Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Post $post
     *
     * @return Post
     */
    public function getAction(Post $post)
    {
        return $post;
    }

    /**
     * Edit a post
     *
     * @Route("/{id}", requirements={"id"="\d+"})
     * @Method("PUT")
     *
     * @SecureParam("post", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Posts",
     *     description="Edit a post",
     *     input="Civix\ApiBundle\Form\Type\PostType",
     *     output={
     *         "class"="Civix\CoreBundle\Entity\Post",
     *         "groups"={"Default"},
     *         "parsers" = {
     *             "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *         }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         404="Post Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Request $request
     * @param Post $post
     *
     * @return Post|\Symfony\Component\Form\Form
     */
    public function putAction(Request $request, Post $post)
    {
        $form = $this->createForm(PostType::class, $post);
        $form->submit($request->request->all(), false);

        if ($form->isValid()) {
            $this->manager->savePost($post);

            return $post;
        }

        return $form;
    }

    /**
     * Boost a post
     *
     * @Route("/{id}", requirements={"id"="\d+"})
     * @Method("PATCH")
     *
     * @SecureParam("post", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Posts",
     *     description="Boost a post",
     *     output={
     *         "class"="Civix\CoreBundle\Entity\Post",
     *         "groups"={"Default"},
     *         "parsers" = {
     *             "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *         }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Post Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Post $post
     *
     * @return Post
     */
    public function patchAction(Post $post)
    {
        if (!$post->isBoosted()) {
            $this->manager->boostPost($post);
        }

        return $post;
    }

    /**
     * Delete a post
     * 
     * @Route("/{id}", requirements={"id"="\d+"})
     * @Method("DELETE")
     *
     * @SecureParam("post", permission="delete")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Posts",
     *     description="Delete a post",
     *     statusCodes={
     *         204="Success",
     *         404="Post Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param Post $post
     */
    public function deleteAction(Post $post)
    {
        $manager = $this->getDoctrine()->getManager();
        $manager->remove($post);
        $manager->flush();
    }

    /**
     * Vote for a post
     *
     * @Route("/{id}/vote", requirements={"id"="\d+"})
     * @Method("POST")
     *
     * @ParamConverter("post", class="Civix\CoreBundle\Entity\Post")
     * @ParamConverter("vote", options={"mapping"={"post"="post", "loggedInUser"="user"}}, converter="doctrine.param_converter")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Posts",
     *     description="Vote for a post",
     *     input="Civix\ApiBundle\Form\Type\VoteType",
     *     output={
     *         "class"="Civix\CoreBundle\Entity\Post\Vote",
     *         "groups"={"Default"},
     *         "parsers" = {
     *             "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *         }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         404="Post Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Request $request
     * @param Post $post
     * @param Vote $vote
     *
     * @return Vote|\Symfony\Component\Form\Form
     */
    public function postVoteAction(Request $request, Post $post, Vote $vote = null)
    {
        if ($vote === null) {
            $vote = new Vote();
            $vote->setUser($this->getUser());
            $vote->setPost($post);
        }
        $form = $this->createForm(VoteType::class, $vote);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            $this->manager->voteOnPost($vote);

            return $vote;
        }

        return $form;
    }

    /**
     * Delete a post's vote.
     *
     * @Route("/{id}/vote", requirements={"id"="\d+"})
     * @Method("DELETE")
     *
     * @ParamConverter("post", class="Civix\CoreBundle\Entity\Post")
     * @ParamConverter("vote", options={"mapping"={"loggedInUser"="user", "post"="post"}}, converter="doctrine.param_converter")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Posts",
     *     description="Delete a post's vote",
     *     statusCodes={
     *         204="Success",
     *         400="Bad Request",
     *         404="Post or Vote Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Vote $vote
     */
    public function deleteVoteAction(Vote $vote)
    {
        $this->manager->unvotePost($vote);
    }

    /**
     * Mark a post as a spam
     *
     * @Route("/{id}/spam", requirements={"id"="\d+"})
     * @Method("POST")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Posts",
     *     description="Mark a post as a spam",
     *     statusCodes={
     *         400="Bad Request",
     *         404="Post Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Post $post
     */
    public function postSpamAction(Post $post)
    {
        $post->markAsSpam($this->getUser());
        $this->manager->savePost($post);
    }

    /**
     * Mark a post as not a spam.
     *
     * @Route("/{id}/spam", requirements={"id"="\d+"})
     * @Method("DELETE")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Posts",
     *     description="Mark a post as not a spam",
     *     statusCodes={
     *         204="Success",
     *         400="Bad Request",
     *         404="Post or Vote Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Post $post
     */
    public function deleteSpamAction(Post $post)
    {
        $post->markAsNotSpam($this->getUser());
        $this->manager->savePost($post);
    }

    /**
     * List all the responses for a given post.
     *
     * @Route("/{id}/responses")
     * @Method("GET")
     *
     * @SecureParam("post", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Posts",
     *     description="List the responses for a given post.",
     *     output="array",
     *     statusCodes={
     *         403="Access Denied",
     *         404="Question Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Post $post
     *
     * @return array
     */
    public function getResponsesAction(Post $post)
    {
        $query = new PostResponsesQuery($this->em);

        return $query($post);
    }

    /**
     * List analytics data for a given post.
     *
     * **Output format**
     *
     *     {
     *         total: {
     *             upvotes: 20,
     *             downvotes: 10
     *         },
     *         representatives: [
     *             {
     *                 id: 1,
     *                 first_name: 'John',
     *                 last_name: 'Dow',
     *                 official_title: 'Vice President',
     *                 upvotes: 10,
     *                 downvotes: 7,
     *                 user: true,
     *                 author: false
     *             },
     *             //...
     *         ],
     *         most_popular: [
     *             {
     *                 id: 1,
     *                 first_name: 'John',
     *                 last_name: 'Dow',
     *                 official_title: 'Vice President',
     *                 upvotes: 10,
     *                 downvotes: 7
     *             },
     *             //...
     *         ]
     *     }
     *
     * @Route("/{id}/analytics")
     * @Method("GET")
     *
     * @SecureParam("post", permission="view")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Posts",
     *     description="List the analytics for a given post.",
     *     output="array",
     *     statusCodes={
     *         403="Access Denied",
     *         404="Question Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Post $post
     *
     * @return array
     */
    public function getVotesAnalyticsAction(Post $post)
    {
        $query = new PostVoteAnalyticsQuery($this->em->getConnection());

        return $query($post, $this->getUser());
    }
}
