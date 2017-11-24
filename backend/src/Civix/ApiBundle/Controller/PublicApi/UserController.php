<?php

namespace Civix\ApiBundle\Controller\PublicApi;

use FOS\RestBundle\Controller\Annotations as REST;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Civix\ApiBundle\Controller\BaseController;
use Civix\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Route("/users")
 */
class UserController extends BaseController
{
    /**
     * @REST\Get("")
     *
     * @REST\QueryParam(name="username", requirements="\w+", description="Username")
     * @REST\QueryParam(name="email", requirements="\w+", description="Email")
     * @REST\QueryParam(name="phone", requirements=@PhoneNumber(), description="Phone number")
     *
     * @ApiDoc(
     *     section="Public",
     *     resource=true,
     *     description="Check if a user with given username, email OR phone exists",
     *     statusCodes={
     *         204="User exists",
     *         400="Username, email or phone are empty",
     *         404="User doesn't exist"
     *     }
     * )
     * @param Request $request
     * @return void
     */
    public function getUserAction(Request $request): void
    {
        $query = $request->query;
        if (!$query->has('username') && !$query->has('email') && !$query->has('phone')) {
            throw new BadRequestHttpException('Username, email or phone value should not be blank.');
        }
        $exists = $this->getDoctrine()
            ->getRepository(User::class)
            ->existsByUsernameOrEmailOrPhone($query->get('username'), $query->get('email'), $query->get('phone'));

        if (!$exists) {
            throw $this->createNotFoundException();
        }
    }

    /**
     * @REST\Get("/")
     *
     * @ApiDoc(
     *     section="Public"
     * )
     * @param Request $request
     * @return Response
     */
    public function getUsers(Request $request): Response
    {
        $users = $this->getDoctrine()
            ->getRepository(User::class)
            ->findBy(['username' => $request->query->get('username')]);

        return $this->createJSONResponse(
            $this->jmsSerialization($users, ['api-public'])
        );
    }
}
