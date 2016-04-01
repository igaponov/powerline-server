<?php

namespace Civix\ApiBundle\Controller;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Entity\UserGroupManager;
use Civix\CoreBundle\Event\GroupEvent;
use Civix\CoreBundle\Event\GroupEvents;
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
     * @Route("/", name="civix_api_groups_by_user")
     * @Method("GET")
     */
    public function getGroupsAction()
    {
        $entityManager = $this->getDoctrine()->getManager();

        $groups = $entityManager->getRepository(Group::class)
                ->getGroupsByUser($this->getUser());

        $response = new Response($this->jmsSerialization($groups, ['api-groups']));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/", name="civix_api_groups_create")
     * @Method("POST")
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

        $password = substr(base_convert(sha1(uniqid(mt_rand(), true)), 16, 36), 0, 9);
        $encoder = $this->get('security.encoder_factory')->getEncoder($group);
        $encodedPassword = $encoder->encodePassword($password, $group->getSalt());
        $group->setPassword($encodedPassword)->setOwner($this->getUser());

        $em->persist($group);
        $em->flush();

        $event = new GroupEvent($group);
        $this->get('event_dispatcher')->dispatch(GroupEvents::CREATED, $event);

        $this->get('civix_core.group_manager')
            ->joinToGroup($this->getUser(), $group);
        $em->flush();

        $this->get('civix_core.email_sender')
            ->sendUserRegistrationSuccessGroup($group, $password);
        $em->getRepository(Group::class)
            ->getTotalMembers($group);

        return $this->createJSONResponse($this->jmsSerialization($group, ['api-info']), 201);
    }

    /**
     * @todo duplicate getGroupsAction?
     * 
     * @Route("/user-groups/", name="civix_api_groups_by_user2")
     * @Method("GET")
     */
    public function getUserGroupsAction()
    {
        $groups = $this->getDoctrine()->getRepository(Group::class)
            ->getUserGroupsByUser($this->getUser());

        $response = new Response($this->jmsSerialization($groups, ['api-groups']));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
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
     *     section="Group",
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
    		new JsonResponse(['error' => 'The user is not member of the group'], 404);
    	}
    	
    	if(!$group->isManager($user))
    	{
    		new JsonResponse(['error' => 'The user is already group manager of this group'], 404);
    	}
    	
    	// Create the new relation for group and user as manager
    	$user_group_manager = new UserGroupManager($user, $group);
    	$entityManager->persist($user_group_manager);
    	$entityManager->flush();
    	
    	// Add the relation in the group object
    	$group->addManagerUser($user_group_manager);
    	
    	$entityManager->persist($group);
    	$entityManager->flush();
    	
    	new JsonResponse([], 204);
    }
    
    /**
     * @Route("/join/{id}", name="civix_api_groups_join")
     * @Method("POST")
     * @ParamConverter(
     *      "group",
     *      class="CivixCoreBundle:Group",
     *      options={"repository_method" = "getGroupByIdAndType"}
     * )
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
     * @Route("/unjoin/{id}", name="civix_api_groups_unjoin")
     * @Method("DELETE")
     * @ParamConverter(
     *      "group",
     *      class="CivixCoreBundle:Group",
     *      options={"repository_method" = "getGroupByIdAndType"}
     * )
     */
    public function unjoinFromGroup(Group $group)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $successRespContent = json_encode(['success' => 'ok']);

        $this->get('civix_core.group_manager')->unjoinGroup($this->getUser(), $group);

        $response->setContent($successRespContent);

        return $response;
    }

    /**
     * @Route("/info/{group}", requirements={"group"="\d+"}, name="civix_api_groups_information")
     * @Method("GET")
     * @ParamConverter("group", class="CivixCoreBundle:Group")
     */
    public function getInformationAction(Request $request, $group)
    {
        $entityManager = $this->getDoctrine()->getManager();

        if (!$group) {
            throw $this->createNotFoundException();
        }

        $count = $entityManager->getRepository(Group::class)
                ->getTotalMembers($group);

        $response = new Response($this->jmsSerialization($group, ['api-info']));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/invites", name="civix_api_groups_invites")
     * @Method("GET")
     */
    public function getInvitesAction(Request $request)
    {
        $response = new Response($this->jmsSerialization($this->getUser()->getInvites(), ['api-groups']));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route(
     *     "/invites/{status}/{group}",
     *     requirements={"group"="\d+", "status"="approve|reject"},
     *     name="civix_api_groups_invites_approval"
     * )
     * @Method("POST")
     * @ParamConverter("group", class="CivixCoreBundle:Group")
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
     * @Route(
     *     "/{group}/fields",
     *     requirements={"group"="\d+"},
     *     name="civix_api_groups_fields"
     * )
     * @Method("GET")
     */
    public function getGroupRequiredFields(Group $group)
    {
        $response = new Response($this->jmsSerialization($group->getFields(), ['api-groups-fields']));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route(
     *     "/{group}/users",
     *     requirements={"group"="\d+"},
     *     name="civix_api_groups_users"
     * )
     * @Method("GET")
     *
     * @ApiDoc(
     *     resource=true,
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
     *     }
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
