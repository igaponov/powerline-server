<?php
namespace Civix\ApiBundle\Controller\V2;

use Civix\CoreBundle\Entity\User;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
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
     * @ParamConverter("user", options={"mapping" = {"id" = "id", "loggedInUser" = "follower"}, "repository_method" = "findWithFollowerById", "map_method_signature" = true}, converter="doctrine.param_converter")
     *
     * @ApiDoc(
     *     authentication = true,
     *     resource=true,
     *     section="Users",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\User",
     *          "groups" = {"api-info", "api-full-info", "user-karma", "discount-code"},
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
     * @View(serializerGroups={"api-info", "user-karma", "discount-code"})
     *
     * @param Request $request
     * @param User $user
     *
     * @return User
     */
    public function getAction(Request $request, User $user): User
    {
        if ($user->getFollowers()->count() && $user->getFollowers()->first()->isActive()) {
            /** @var View $configuration */
            $configuration = $request->attributes->get('_template');
            $groups = $configuration->getSerializerGroups();
            $groups[] = 'api-full-info';
            $configuration->setSerializerGroups($groups);
        }

        return $user;
    }
}