<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Service\PostManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as REST;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Route("/user/shared-posts")
 */
class UserSharedPostController
{
    /**
     * @var PostManager
     */
    private $manager;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(
        PostManager $manager,
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $em
    ) {
        $this->manager = $manager;
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
    }

    /**
     * @REST\Put("/{id}", requirements={"id"="\d+"})
     *
     * @ParamConverter("post", options={
     *     "mapping" = {"id" = "id", "loggedInUser" = "user"},
     *     "repository_method" = "findPostWithUserVote",
     *     "map_method_signature" = true
     * }, converter="doctrine.param_converter")
     *
     * @Security("is_granted('share', post)")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Posts",
     *     description="Share a post with followers",
     *     requirements={
     *         {"name"="id", "dataType"="integer", "description"="Post id"}
     *     },
     *     statusCodes={
     *         204="Success",
     *         404="Post Not Found"
     *     }
     * )
     *
     * @param Post $post
     */
    public function putAction(Post $post): void
    {
        $token = $this->tokenStorage->getToken();
        /** @var User $sharer */
        $sharer = $token->getUser();
        try {
            $this->manager->sharePost($post, $sharer);
        } catch (\DomainException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        $this->em->flush();
    }
}