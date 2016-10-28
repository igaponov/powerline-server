<?php

namespace Civix\ApiBundle\Controller;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Entity\UserGroupManager;
use Civix\CoreBundle\Event\GroupEvent;
use Civix\CoreBundle\Event\GroupEvents;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use JMS\Serializer\Exception\RuntimeException;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Group API controller for groups
 * 
 * @Route("/groups")
 */
class GroupController extends BaseController
{
    /**
     * Checks if the current user is group owner for a group given
     *
     *     curl -i -X GET -G 'http://domain.com/api/groups/is-owner/{id}' -d ''
     *
     * **Input Parameters**
     *
     *     id: the group identifier
     *
     * **Output Format**
     *
     * If successful:
     *
     *     {"true"}
     *
     * If error:
     *
     *     ["error","some error message"]
     *
     * @ApiDoc(
     *     https = true,
     *     authentication = true,
     *     resource=true,
     *     section="Groups",
     *     description="Checks if the current user is group owner for a group given",
     *     views = { "default"},
     *     output = "",
     *     requirements={
     *     },
     *     tags={
     *         "stable" = "#89BF04",
     *         "GET" = "#0f6ab4",
     *         "owner group",
     *     },
     *     filters={
     *     },
     *     parameters={
     *     },
     *     input = {
     *    "class" = "",
     *        "options" = {"method" = "GET"},
     *       },
     *     statusCodes={
     *          204="Returned when successful",
     *          400="Returned when incorrect login or password",
     *          405="Method Not Allowed"
     *     }
     * )
     *
     * @Route("/is-owner/{id}", name="civix_api_groups_is_owner")
     * @Method("GET")
     * @ParamConverter(
     *      "group",
     *      class="CivixCoreBundle:Group",
     *      options={"repository_method" = "getGroupByIdAndType"}
     * )
     * @param Group $group
     * @return JsonResponse
     */
    public function isGroupOwnerAction(Group $group)
    {
    	/** @var $user User */
    	$user = $this->getUser();
    	
    	if(!$group->isOwner($user))
    	{
    		return new JsonResponse(
                ['error' => 'The user is not owner of the group'], 
                Codes::HTTP_BAD_REQUEST
            );
    	}

    	return new JsonResponse('', Codes::HTTP_NO_CONTENT);
    }
    
    /**
     * Deprecated
     *
     * Checks if the current user is group member for a group given.
     * 
     * Note that a group owner is a group member as default definition.
     *
     *     curl -i -X GET -G 'http://domain.com/api/groups/is-member/{id}' -d ''
     *
     * **Input Parameters**
     *
     *     id: the group identifier
     *
     * **Output Format**
     *
     * If successful:
     *
     *     {"true"}
     *
     * If error:
     *
     *     ["error","some error message"]
     *
     * @ApiDoc(
     * 	   https = true,
     *     authentication = true,
     *     resource=true,
     *     section="Groups",
     *     description="Checks if the current user is group member for a group given.",
     *     views = { "default"},
     *     output = "",
     *     requirements={
     *     },
     *     tags={
     *         "stable" = "#89BF04",
     *         "GET" = "#0f6ab4",
     *         "member group",
     *     },
     *     filters={
     *     },
     *     parameters={
     *     },
     *     input = {
     *   	"class" = "",
     *	    "options" = {"method" = "GET"},
     *	   },
     *     statusCodes={
     *          200="Returned when successful",
     *          400="Returned when incorrect login or password",
     *          405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     *
     * @Route("/is-member/{id}", name="civix_api_groups_is_member")
     * @Method("GET")
     * @ParamConverter(
     *      "group",
     *      class="CivixCoreBundle:Group",
     *      options={"repository_method" = "getGroupByIdAndType"}
     * )
     */
    public function isGroupMemberAction(Request $request, Group $group)
    {
    	/** @var $user User */
    	$user = $this->getUser();
    	 
    	// Group owner is group member as default
    	if($group->isOwner($user))
    	{
    		return new JsonResponse([TRUE], 204);
    	}
    	
    	if(!$group->isMember($user))
    	{
    		return new JsonResponse(['error' => 'The user is not member of the group'], 404);
    	}
    
    	return new JsonResponse([TRUE], 204);
    }

    /**
     * Deprecated
     *
     * Checks if the current user is group manager for a group given.
     * 
     * By definition, a group manager MUST BE a group member too.
     *
     *     curl -i -X GET -G 'http://domain.com/api/groups/is-manager/{id}' -d ''
     *
     * **Input Parameters**
     *
     *     id: the group identifier
     *
     * **Output Format**
     *
     * If successful:
     *
     *     {"true"}
     *
     * If error:
     *
     *     ["error","some error message"]
     *
     * @ApiDoc(
     * 	   https = true,
     *     authentication = true,
     *     resource=true,
     *     section="Groups",
     *     description="Checks if the current user is group manager for a group given.",
     *     views = { "default"},
     *     output = "",
     *     requirements={
     *     },
     *     tags={
     *         "stable" = "#89BF04",
     *         "GET" = "#0f6ab4",
     *         "manager group",
     *     },
     *     filters={
     *     },
     *     parameters={
     *     },
     *     input = {
     *   	"class" = "",
     *	    "options" = {"method" = "GET"},
     *	   },
     *     statusCodes={
     *          200="Returned when successful",
     *          400="Returned when incorrect login or password",
     *          405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     *
     * @Route("/is-manager/{id}", name="civix_api_groups_is_manager")
     * @Method("GET")
     * @ParamConverter(
     *      "group",
     *      class="CivixCoreBundle:Group",
     *      options={"repository_method" = "getGroupByIdAndType"}
     * )
     */
    public function isGroupManagerAction(Request $request, Group $group)
    {
    	/** @var $user User */
    	$user = $this->getUser();
    
    	// By definition, a group manager MUST BE a group member too.
   		if(!$group->isMember($user))
    	{
    		return new JsonResponse(['error' => 'The user is not member of the group'], 404);
    	}
    	
    	if(!$group->isManager($user))
    	{
    		return new JsonResponse(['error' => 'The user is not group manager of this group'], 404);
    	}
    
    	return new JsonResponse([TRUE], 204);
    }
    
	/**
     * Returns groups.
     * Deprecated, use `GET /api/v2/groups` instead.
     * 
     * @Route("/", name="civix_api_groups")
     * @Method("GET")
     * 
     * @ApiDoc(
     *     section="Groups",
     *     description="Returns groups",
     *     deprecated=true
     * )
     */
    public function getGroupsAction()
    {
        $entityManager = $this->getDoctrine()->getManager();

        $groups = $entityManager->getRepository(Group::class)
                ->getGroupsByUser();

        $response = new Response($this->jmsSerialization($groups, ['api-groups']));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Deprecated, use `POST /api/v2/user/groups` instead.
     *
     * @Route("/", name="civix_api_groups_create")
     * @Method("POST")
     *
     * @ApiDoc(
     *     section="Groups",
     *     deprecated=true
     * )
     */
    public function createGroupAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $groups = $em->getRepository(Group::class)->findBy(['owner' => $this->getUser()]);

        if (count($groups) > 4) {
            return $this->createJSONResponse(json_encode([
                'error' => 'You have reached a limit for creating groups',
            ]), 403);
        }

        /** @var Group $group */
        $group = $this->jmsDeserialization($request->getContent(), Group::class, ['api-create-by-user']);
        $group->init();

        $errors = $this->getValidator()->validate($group, ['user-registration']);

        if (count($errors) > 0) {
            return $this->createJSONResponse(json_encode(['errors' => $this->transformErrors($errors)]), 400);
        }

        $em->persist($group);
        $em->flush();

        $event = new GroupEvent($group);
        $this->get('event_dispatcher')->dispatch(GroupEvents::REGISTERED, $event);

        $this->get('civix_core.group_manager')
            ->joinToGroup($this->getUser(), $group);
        $em->flush();

        return $this->createJSONResponse($this->jmsSerialization($group, ['api-info']), 201);
    }

    /**
     * Return user's groups.
     * Deprecated, use `GET /api/v2/user/groups` instead.
     * 
     * @Route("/user-groups/", name="civix_api_groups_by_user")
     * @Method("GET")
     * 
     * @ApiDoc(
     *     authentication = true,
     *     description="Return user's groups",
     *     section="Groups",
     *     output={
     *          "class" = "array<Civix\CoreBundle\Entity\UserGroup>",
     *          "groups" = {"api-groups"}
     *     },
     *     deprecated=true
     * )
     */
    public function getUserGroupsAction()
    {
        $groups = $this->getDoctrine()->getRepository(Group::class)
            ->getByUserQuery($this->getUser())->getResult();

        $response = new Response($this->jmsSerialization($groups, ['api-groups']));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Fetch the groups more popular for the current user.
     * Deprecated, use `GET /api/v2/groups?sort=popularity&sort_dir=DESC&exclude_owned=true` instead.
     *
     *     curl -i -X POST -G 'http://domain.com/api/groups/user-groups/' -d ''
     *
     * **Input Parameters**
     *
     *     None
     *
     * **Output Format**
     *
     * If successful:
     *
     *     {""}
     *
     * If error:
     *
     *     ["error","some error message"]
     *
     * @ApiDoc(
     * 	   https = true,
     *     authentication = false,
     *     section="Groups",
     *     description="Fetch the groups more popular for the current user",
     *     views = { "default"},
     *     output = "",
     *     requirements={
     *     },
     *     tags={
     *         "stable" = "#89BF04",
     *         "GET" = "#0f6ab4",
     *         "popular groups",
     *     },
     *     filters={
     *     },
     *     parameters={
     *     },
     *     input = {
     *   	"class" = "",
     *	    "options" = {"method" = "GET"},
     *	   },
     *     statusCodes={
     *          200="Returned when successful",
     *          400="Returned when incorrect login or password",
     *          405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     *
     * @Route("/popular", name="civix_api_groups_popular_groups")
     * @Method("GET")
     */
    public function getPopularGroupsAction()
    {
        $entityManager = $this->getDoctrine()->getManager();

        $groups = $entityManager->getRepository(Group::class)
            ->getPopularGroupsByUser($this->getUser());

        $response = new Response($this->jmsSerialization($groups, ['api-groups']));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Fetch the groups with news for the current user.
     * Deprecated, use `GET /api/v2/groups?sort=created_at&sort_dir=DESC&exclude_owned=true` instead.
     *
     *     curl -i -X POST -G 'http://domain.com/api/groups/new' -d ''
     *
     * **Input Parameters**
     *
     *     None
     *
     * **Output Format**
     *
     * If successful:
     *
     *     {""}
     *
     * If error:
     *
     *     ["error","some error message"]
     *
     * @ApiDoc(
     * 	   https = true,
     *     authentication = false,
     *     section="Groups",
     *     description="Fetch the groups with news for the current user",
     *     views = { "default"},
     *     output = "",
     *     requirements={
     *     },
     *     tags={
     *         "stable" = "#89BF04",
     *         "GET" = "#0f6ab4",
     *         "new group",
     *     },
     *     filters={
     *     },
     *     parameters={
     *     },
     *     input = {
     *   	"class" = "",
     *	    "options" = {"method" = "GET"},
     *	   },
     *     statusCodes={
     *          200="Returned when successful",
     *          400="Returned when incorrect login or password",
     *          405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     *
     * @Route("/new", name="civix_api_groups_new_groups")
     * @Method("GET")
     */
    public function getNewGroupsAction()
    {
        $entityManager = $this->getDoctrine()->getManager();

        $groups = $entityManager->getRepository(Group::class)
            ->getNewGroupsByUser($this->getUser());

        $response = new Response($this->jmsSerialization($groups, ['api-groups']));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Join a group member as group manager for a group
     *
     *     curl -i -X POST -G 'http://domain.com/api/groups/join-group-manager/{id}' -d ''
     *
     * **Input Parameters**
     *
     *     id: the group identifier
     *
     * **Output Format**
     *
     * If successful:
     *
     *     {""}
     *
     * If error:
     *
     *     ["error","some error message"]
     *
     * @ApiDoc(
     * 	   https = true,
     *     authentication = false,
     *     resource=true,
     *     section="Groups",
     *     description="oin a group member as group manager for a group",
     *     views = { "default"},
     *     output = "",
     *     requirements={
	 *     },
     *     tags={
	 *         "stable" = "#89BF04",
	 *         "POST" = "#10a54a",
	 *         "join group manager",
	 *     },
     *     filters={
     *     },
     *     parameters={
	 *     },
     *     input = {
	 *   	"class" = "",
	 *	    "options" = {"method" = "POST"},
	 *	   },
     *     statusCodes={
     *          200="Returned when successful",
     *          400="Returned when incorrect login or password",
     *          405="Method Not Allowed"
     *     }
     * )
     * 
     * @Route("/join-group-manager/{id}", name="civix_api_groups_join_group_manager")
     * @Method("POST")
     * @ParamConverter(
     *      "group",
     *      class="CivixCoreBundle:Group",
     *      options={"repository_method" = "getGroupByIdAndType"}
     * )
     */
    public function joinToGroupAsGroupManagerAction(Request $request, Group $group)
    {
    	$entityManager = $this->getDoctrine()->getManager();
    	/** @var $user User */
    	$user = $this->getUser();
    	
    	if(!$group->isMember($user))
    	{
    		return new JsonResponse(['error' => 'The user is not member of the group'], 404);
    	}
    	
    	if($group->isManager($user))
    	{
    		return new JsonResponse(['error' => 'The user is already group manager of this group'], 404);
    	}
    	
    	// Create the new relation for group and user as manager
    	$user_group_manager = new UserGroupManager($user, $group);
    	$entityManager->persist($user_group_manager);
    	$entityManager->flush();
    	
    	// Add the relation in the group object
    	$group->addManager($user_group_manager);
    	
    	$entityManager->persist($group);
    	$entityManager->flush();
    	
    	return new JsonResponse([], 204);
    }

    /**
     * Deprecated, use `PUT /api/v2/user/groups/{id}` instead
     *
     * @Route("/join/{id}", name="civix_api_groups_join")
     * @Method("POST")
     * @ParamConverter(
     *      "group",
     *      class="CivixCoreBundle:Group",
     *      options={"repository_method" = "getGroupByIdAndType"}
     * )
     *
     * @ApiDoc(
     *     section="Groups",
     *     deprecated=true
     * )
     *
     * @param Request $request
     * @param Group $group
     *
     * @return Response
     */
    public function joinToGroupAction(Request $request, Group $group)
    {
        $entityManager = $this->getDoctrine()->getManager();
        /** @var $user User */
        $user = $this->getUser();
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $responseContentArray = [];

        //check invites
        $isNeedToCheckPasscode = $this->get('civix_core.group_manager')
                ->isNeedCheckPasscode($group, $user);

        $serializerGroups = [];
        if ($group->getFillFieldsRequired()) {
            $serializerGroups[] = 'api-group-field';
        }
        if ($isNeedToCheckPasscode) {
            $serializerGroups[] = 'api-group-passcode';
        }

        if (!empty($serializerGroups)) {
            try {
                $worksheet = $this->jmsDeserialization(
                    $request->getContent(),
                    'Civix\CoreBundle\Model\Group\Worksheet',
                    $serializerGroups
                );
                $worksheet->setUser($user);
                $worksheet->setGroup($group);

                //check passcode
                if ($isNeedToCheckPasscode &&
                    $worksheet->getPasscode() != $group->getMembershipPasscode()
                ) {
                    throw new AccessDeniedHttpException('Incorrect passcode');
                }
            } catch (RuntimeException $exc) {
                //incorrect json or empty
                if ($isNeedToCheckPasscode) {
                    throw new AccessDeniedHttpException('Incorrect passcode');
                } else {
                    throw new BadRequestHttpException('Incorrect request body');
                }
            }

            $errors = $this->getValidator()->validate($worksheet, $serializerGroups);
            if (count($errors) > 0) {
                $response->setStatusCode(400)->setContent(
                    json_encode(['errors' => $this->transformErrors($errors)])
                );

                return $response;
            }
        }

        if (!$this->get('civix_core.package_handler')->getPackageStateForGroupSize($group)->isAllowed()) {
            $response->setStatusCode(403)->setContent(
                json_encode(['error' => 'The group is full'])
            );

            return $response;
        }

        $changedUser = $this->get('civix_core.group_manager')
            ->joinToGroup($user, $group);

        if ($changedUser instanceof User) {
            //save fields values
            if ($group->getFillFieldsRequired()) {
                foreach ($worksheet->getFields() as $fieldValue) {
                    /** @var Group\FieldValue $entity */
                    $entity = $entityManager->merge($fieldValue);
                    $entityManager->persist($entity);
                }
            }

            $entityManager->persist($changedUser);
            $entityManager->flush();
        }

        //check status of join
        $userGroup = $entityManager
            ->getRepository(UserGroup::class)
            ->isJoinedUser($group, $user);
        $responseContentArray['status'] = $userGroup->getStatus();

        $response->setContent(json_encode($responseContentArray));

        return $response;
    }

    /**
     * Deprecated, use `DELETE /api/v2/user/groups/{id}` instead
     *
     * @Route("/unjoin/{id}", name="civix_api_groups_unjoin")
     * @Method("DELETE")
     * @ParamConverter(
     *      "group",
     *      class="CivixCoreBundle:Group",
     *      options={"repository_method" = "getGroupByIdAndType"}
     * )
     *
     * @ApiDoc(
     *     section="Groups",
     *     deprecated=true
     * )
     *
     * @param Group $group
     *
     * @return Response
     */
    public function unjoinFromGroupAction(Group $group)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $successRespContent = json_encode(['success' => 'ok']);

        $this->get('civix_core.group_manager')->unjoinGroup($this->getUser(), $group);

        $response->setContent($successRespContent);

        return $response;
    }

    /**
     * Returns group information.
     * Deprecated, use /api/v2/groups/{id} instead.
     * 
     * @Route("/info/{group}", requirements={"group"="\d+"}, name="civix_api_groups_information")
     * @Method("GET")
     * @ParamConverter("group", class="CivixCoreBundle:Group")
     * 
     * @ApiDoc(
     *     section="Groups",
     *     description="Returns group information",
     *     deprecated=true
     * )
     */
    public function getInformationAction(Request $request, $group)
    {
        $entityManager = $this->getDoctrine()->getManager();

        if (!$group) {
            throw $this->createNotFoundException();
        }

        $response = new Response($this->jmsSerialization($group, ['api-info']));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Returns list of invites
     * Deprecated, use `GET /api/v2/user/invites` instead
     *
     * @Route("/invites", name="civix_api_groups_invites")
     * @Method("GET")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Groups",
     *     description="List of invites",
     *     output="ArrayCollection<Civix\CoreBundle\Entity\Group>",
     *     statusCodes={
     *         200="Returns invites",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-groups"})
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getInvitesAction()
    {
        return $this->getUser()->getInvites();
    }

    /**
     * Deprecated, use `PATCH /api/v2/group/{id}/invites/{user}` and `DELETE /api/v2/group/{id}/invites/{user}` instead
     *
     * @Route(
     *     "/invites/{status}/{group}",
     *     requirements={"group"="\d+", "status"="approve|reject"},
     *     name="civix_api_groups_invites_approval"
     * )
     *
     * @Method("POST")
     * @ParamConverter("group", class="CivixCoreBundle:Group")
     *
     * @ApiDoc(
     *     section="Groups",
     *     deprecated=true
     * )
     *
     * @param $status
     * @param Group $group
     * @return Response
     */
    public function invitesApprovalAction($status, Group $group)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        $user = $this->getUser();

        if ($status == 'reject') {
            if (true === $user->getInvites()->contains($group)) {
                $user->removeInvite($group);
            } else {
                $response->setStatusCode(405);
            }
        }
        if ($status == 'approve') {
            if (false === $user->getGroups()->contains($group) && true === $user->getInvites()->contains($group)) {
                $this->get('civix_core.group_manager')
                    ->joinToGroup($user, $group);
            } else {
                $response->setStatusCode(405);
            }
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return $response;
    }

    /**
     * Returns list of required fields from group
     * Deprecated, use `GET /api/v2/groups/{group}/fields` instead
     *
     * @Route(
     *     "/{group}/fields",
     *     requirements={"group"="\d+"},
     *     name="civix_api_groups_fields"
     * )
     * @Method("GET")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="List of required fields from group",
     *     output="ArrayCollection<Civix\CoreBundle\Entity\Group\GroupField>",
     *     statusCodes={
     *         200="Returns fields",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     *
     * @View(serializerGroups={"api-groups-fields"})
     *
     * @param Group $group
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRequiredFieldsAction(Group $group)
    {
        return $group->getFields();
    }

    /**
     * List of users from group
     * Deprecated, use `GET /api/v2/groups/{id}/users` instead.
     *
     * @Route(
     *     "/{group}/users",
     *     requirements={"group"="\d+"},
     *     name="civix_api_groups_users"
     * )
     * @Method("GET")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="List of users from group",
     *     filters={
     *             {"name"="limit", "dataType"="integer"},
     *             {"name"="page", "dataType"="integer"}
     *     },
     *     statusCodes={
     *         200="Returns users",
     *         400="Bad request",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     *
     * @param Request $request
     * @param $group
     *
     * @return Response
     */
    public function getGroupUsersAction(Request $request, $group)
    {
        $limit = $request->query->get('limit', 50);
        $page = $request->query->get('page', 1);
        $users = $this->getDoctrine()->getRepository(User::class)
            ->getUsersByGroup($group, $page, $limit);

        $response = new Response($this->jmsSerialization($users, ['api-short-info']));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
