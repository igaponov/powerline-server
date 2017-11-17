<?php

namespace Civix\ApiBundle\Controller\V2_2\User;

use Civix\Component\Doctrine\ORM\Cursor;
use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations as REST;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Route("/user/blocked-users")
 */
class BlockedUsersController
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        EntityManager $em,
        TokenStorageInterface $tokenStorage
    ) {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @REST\Get("")
     *
     * @REST\QueryParam(name="cursor", requirements="\d+", default="0")
     * @REST\QueryParam(name="limit", requirements=@Assert\Range(min="1", max="20"), default="20")
     *
     * @ApiDoc(
     *     resource=true,
     *     authentication=true,
     *     section="Users",
     *     description="Get blocked users",
     *     output={
     *          "class" = "array<Civix\CoreBundle\Entity\User>",
     *          "groups" = {"user-list"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     }
     * )
     *
     * @REST\View(serializerGroups={"user-list"})
     *
     * @param ParamFetcher $params
     *
     * @return Cursor
     */
    public function getAction(ParamFetcher $params): Cursor
    {
        $token = $this->tokenStorage->getToken();
        $repository = $this->em->getRepository(User::class);

        $query = $repository->getBlockedByUserQueryBuilder($token->getUser());

        return new Cursor($query, $params->get('cursor'), $params->get('limit'));
    }

    /**
     * @REST\Put("/{id}", requirements={"id" = "\d+"})
     *
     * @ParamConverter("user", options={
     *     "mapping" = {"id" = "id", "loggedInUser" = "user"},
     *     "repository_method" = "findUserWithBlockedBy",
     *     "map_method_signature" = true
     * }, converter="doctrine.param_converter")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Users",
     *     description="Block a user",
     *     requirements={
     *          {
     *              "name"="id",
     *              "dataType"="integer",
     *              "requirement"="\d+",
     *              "description"="User's id"
     *          }
     *     },
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\User",
     *          "groups" = {"api-short-info"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     }
     * )
     *
     * @REST\View(serializerGroups={"api-short-info"})
     *
     * @param User $user
     * @return User
     */
    public function putAction(User $user): User
    {
        $token = $this->tokenStorage->getToken();
        if ($user->getBlockedBy()->contains($token->getUser())) {
            throw new BadRequestHttpException('User is blocked already.');
        }
        $user->blockBy($token->getUser());
        $this->em->flush();

        return $user;
    }

    /**
     * @REST\Delete("/{id}", requirements={"id" = "\d+"})
     *
     * @ParamConverter("user", options={
     *     "mapping" = {"id" = "id", "loggedInUser" = "user"},
     *     "repository_method" = "findUserWithBlockedBy",
     *     "map_method_signature" = true
     * }, converter="doctrine.param_converter")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Users",
     *     description="Unblock a user",
     *     requirements={
     *          {
     *              "name"="id",
     *              "dataType"="integer",
     *              "requirement"="\d+",
     *              "description"="User's id"
     *          }
     *     }
     * )
     *
     * @param User $user
     */
    public function deleteAction(User $user): void
    {
        $token = $this->tokenStorage->getToken();
        if ($user->getBlockedBy()->contains($token->getUser())) {
            $user->unblockBy($token->getUser());
            $this->em->flush();
            return;
        }
        throw new BadRequestHttpException('User is not blocked.');
    }
}