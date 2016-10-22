<?php

namespace Civix\ApiBundle\Controller;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroup;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception as HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/users")
 */
class UserController extends BaseController
{
    /**
     * Deprecated, use users resource instead
     *
     * @Route("/find", name="civix_api_user_by_username")
     * @Method("GET")
     *
     * @ApiDoc(
     *     resource=true,
     *     section="Users",
     *     description="Find user by username",
     *     filters={
     *         {"name"="username", "dataType"="string"}
     *     },
     *     statusCodes={
     *         200="Returns user info",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     *
     * @deprecated
     */
    public function findByUsernameAction(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $user = $entityManager->getRepository('CivixCoreBundle:User')
                ->getUserByUsername($this->getUser(), $request->get('username'));

        $response = new Response($this->jmsSerialization($user, array('api-info')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/", name="civix_api_user_list_users")
     * @Method("GET")
     *
     * @ApiDoc(
     *     resource=true,
     *     section="Users",
     *     description="List of users",
     *     filters={
     *             {"name"="q", "dataType"="string"}
     *      },
     *     statusCodes={
     *         200="Returns users",
     *         400="Bad request",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     */
    public function usersAction(Request $request)
    {
        $limit = $request->query->get('max_count', 50);
        $page = $request->query->get('page', 1);
        $offset = ($page - 1) * $limit;
        $q = $request->query->get('q');

        if ($limit > 100) {
            throw new HttpException\BadRequestHttpException();
        }

        if ($request->query->has('q')) {
            $q = $request->query->get('q');
            if (!$q) {
                throw new HttpException\BadRequestHttpException('The query cannot be empty string');
            }
        }

        $users = $this->getDoctrine()->getRepository('CivixCoreBundle:User')->findByParams(array(
            'query' => $q,
            'unfollowing' => $request->query->get('unfollowing', false),
        ), array(), $limit, $offset, $this->getUser());

        $response = new Response($this->jmsSerialization($users, array('api-info')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Deprecated, use `PUT /api/v2/user/micro-petitions/{id}` instead
     *
     * @Route("/self/subscriptions", name="civix_api_user_self_subscriptions_activity")
     * @Method("POST")
     * 
     * @ApiDoc(
     *     section="User Petitions",
     *     description="Subscribe to activity",
     *     statusCodes={
     *         201="Returns null",
     *         400="Bad Request",
     *         404="Not Found",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     * 
     * @param Request $request
     * @return Response
     */
    public function postUserSubscriptionAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        if ($data && !empty($data['id'])) {
            $petitionId = $data['id'];
            /** @var EntityManager $em */
            $em = $this->get('doctrine.orm.entity_manager');
            $petition = $em->getRepository('CivixCoreBundle:Micropetitions\Petition')->find($petitionId);
            $group = $petition->getGroup();

        } else {
            $petition = $group = null;
        }
        $func = function($i, UserGroup $userGroup) use($group) {
            return $group->getId() == $userGroup->getId();
        };
        if (!$petition || !$user->getUserGroups()->exists($func)) {
            throw $this->createNotFoundException();
        }

        $this->get('civix_core.user_manager')->subscribeToPetition($user, $petition);

        return $this->createJSONResponse(null, 201);
    }
}
