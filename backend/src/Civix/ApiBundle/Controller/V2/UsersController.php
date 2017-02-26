<?php
namespace Civix\ApiBundle\Controller\V2;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/users")
 */
class UsersController extends FOSRestController
{
    /**
     * Profile of a user
     *
     * @Route("/{id}")
     * @Method("GET")
     *
     * @ApiDoc(
     *     authentication = true,
     *     resource=true,
     *     section="Users",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\User",
     *          "groups" = {"api-info", "api-full-info"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     description="User's profile",
     *     statusCodes={
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-info"})
     *
     * @param Request $request
     * @param User $user
     *
     * @return User
     */
    public function getAction(Request $request, User $user)
    {
        $userFollow = $this->getDoctrine()->getRepository(UserFollow::class)->findOneBy([
            'user' => $user,
            'follower' => $this->getUser(),
        ]);
        if ($userFollow && $userFollow->isActive()) {
            /** @var View $configuration */
            $configuration = $request->attributes->get('_template');
            $configuration->setSerializerGroups(['api-full-info']);
        }

        return $user;
    }

    /**
     * Get user's posts filtered by logged in user's groups
     *
     * @Route("/{id}/posts")
     * @Method("GET")
     *
     * @QueryParam(name="page", requirements="\d+", default="1")
     * @QueryParam(name="per_page", requirements="(10|20)", default="20")
     *
     * @ApiDoc(
     *     authentication = true,
     *     section="Users",
     *     description="Get user's posts",
     *     output={
     *          "class" = "array<Civix\CoreBundle\Entity\Post> as paginator",
     *          "groups" = {"Default", "api-info"},
     *          "parsers" = {
     *              "Civix\ApiBundle\Parser\PaginatorParser"
     *          }
     *     },
     *     statusCodes={
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"Default", "api-info"})
     *
     * @param ParamFetcher $params
     * @param User $user
     * @return User
     */
    public function getUserPostsAction(ParamFetcher $params, User $user)
    {
        $query = $this->getDoctrine()
            ->getRepository(Post::class)
            ->getFindByUserQuery($user, ['user' => $this->getUser()]);

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }
}