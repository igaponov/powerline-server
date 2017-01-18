<?php
namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\GroupAvatarType;
use Civix\ApiBundle\Form\Type\GroupType;
use Civix\ApiBundle\Form\Type\InviteType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Service\AvatarManager;
use Civix\CoreBundle\Service\Group\GroupManager;
use Doctrine\ORM\EntityManager;
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
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/groups")
 */
class GroupController extends FOSRestController
{
    /**
     * @var EntityManager
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

    /**
     * @var GroupManager
     * @DI\Inject("civix_core.group_manager")
     */
    private $manager;

    /**
     * @var AvatarManager
     * @DI\Inject("civix_core.service.avatar_manager")
     */
    private $avatarManager;

    /**
     * Returns groups.
     *
     * @Route("")
     * @Method("GET")
     *
     * @QueryParam(name="page", requirements="\d+", default="1")
     * @QueryParam(name="per_page", requirements="(10|20)", default="20")
     * @QueryParam(name="sort", requirements="(created_at|popularity)", nullable=true)
     * @QueryParam(name="sort_dir", requirements="(ASC|DESC)", nullable=true)
     * @QueryParam(name="exclude_owned", requirements=".+", description="Exclude groups of current user (use any value)", nullable=true)
     * @QueryParam(name="query", requirements=".+", description="Search groups by query", nullable=true)
     *
     * @ApiDoc(
     *     authentication = true,
     *     resource=true,
     *     section="Groups",
     *     description="Return groups",
     *     output="Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *     statusCodes={
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api-groups"})
     *
     * @param ParamFetcher $params
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getcAction(ParamFetcher $params)
    {
        $query = $this->em->getRepository(Group::class)
            ->getFindByQuery([
                'exclude_owned' => $params->get('exclude_owned') ? $this->getUser() : null,
                'query' => $params->get('query'),
            ], [
                $params->get('sort') => $params->get('sort_dir')
            ]);

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }

    /**
     * Returns a group
     *
     * @Route("/{id}", requirements={"id"="\d+"})
     * @Method("GET")
     *
     * @ParamConverter("group", options={"repository_method"="findWithUser", "mapping" = {"id" = "id", "loggedInUser" = "owner"}}, converter="doctrine.param_converter")
     *
     * @ApiDoc(
     *     authentication = true,
     *     section="Groups",
     *     description="Returns a group",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\Group",
     *          "groups" = {"api-info", "api-full-info", "api-short-info"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-info", "api-full-info", "api-short-info", "user-role"})
     *
     * @param Group $group
     *
     * @return Group
     */
    public function getAction(Group $group)
    {
        return $group;
    }

    /**
     * Updates an user's group
     *
     * @Route("/{id}")
     * @Method("PUT")
     *
     * @SecureParam("group", permission="edit")
     * 
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="",
     *     input="Civix\ApiBundle\Form\Type\GroupType",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\Group",
     *          "groups" = {"api-info"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-info"})
     *
     * @param Request $request
     * @param Group $group
     *
     * @return Group|\Symfony\Component\Form\Form
     */
    public function putAction(Request $request, Group $group)
    {
        $form = $this->createForm(new GroupType(), $group, [
            'validation_groups' => 'user-registration',
        ]);
        $form->submit($request, false);

        if ($form->isValid()) {
            $this->em->persist($group);
            $this->em->flush();

            return $group;
        }

        return $form;
    }

    /**
     * Updates a group's avatar
     *
     * @Route("/{id}/avatar")
     * @Method("PUT")
     *
     * @SecureParam("group", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Updates a group's avatar",
     *     input="Civix\ApiBundle\Form\Type\GroupAvatarType",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\Group",
     *          "groups" = {"api-info"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-info"})
     *
     * @param Request $request
     * @param Group $group
     *
     * @return Group|\Symfony\Component\Form\Form
     */
    public function putAvatarAction(Request $request, Group $group)
    {
        $form = $this->createForm(new GroupAvatarType(), $group, [
            'validation_groups' => 'avatar',
        ]);
        $form->submit($request);

        if ($form->isValid()) {
            $this->em->persist($group);
            $this->em->flush();

            return $group;
        }

        return $form;
    }

    /**
     * Deletes group's avatar
     *
     * @Route("/{id}/avatar")
     * @Method("DELETE")
     *
     * @SecureParam("group", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Deletes group's avatar",
     *     statusCodes={
     *         204="Success",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Group $group
     */
    public function deleteAvatarAction(Group $group)
    {
        $this->avatarManager->deleteAvatar($group);
    }

    /**
     * @Route("/{id}/users", requirements={"id"="\d+"})
     * @Method("GET")
     *
     * @QueryParam(name="page", requirements="\d+", default="1")
     * @QueryParam(name="per_page", requirements="(10|20)", default="20")
     * 
     * @ParamConverter("group")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Returns a list of users from a group",
     *     output="Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *     statusCodes={
     *         400="Bad request",
     *         401="Authorization required",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "user-list"})
     *
     * @param ParamFetcher $params
     * @param $group
     *
     * @return Response
     */
    public function getUsersAction(ParamFetcher $params, Group $group)
    {
        $query = $this->getDoctrine()->getRepository(UserGroup::class)
            ->getFindByGroupQuery($group);

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }

    /**
     * @Route("/{id}/users/{user}", requirements={"id"="\d+"})
     * @Method("DELETE")
     *
     * @SecureParam("group", permission="manage")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Remove a user from a group",
     *     statusCodes={
     *         204="Success",
     *         403="Access Denied",
     *         404="Group or User Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Group $group
     * @param User $user
     */
    public function deleteUsersAction(Group $group, User $user)
    {
        $this->manager->unjoinGroup($user, $group);
    }

    /**
     * @Route("/{group}/users/{user}")
     * @Method("PATCH")
     *
     * @ParamConverter("userGroup", options={"mapping"={"group"="group", "user"="user"}})
     *
     * @SecureParam("userGroup", permission="manage")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Updates a status of an user in a group to `active`",
     *     statusCodes={
     *         204="Success",
     *         404="Group or User Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-info"})
     *
     * @param UserGroup $userGroup
     */
    public function patchUserAction(UserGroup $userGroup)
    {
        $this->manager->approveUser($userGroup);
    }

    /**
     * Deprecated, use `PUT /api/v2/groups/{id}/invites` instead
     *
     * @Route("/{id}/users", requirements={"id"="\d+"})
     * @Method("PUT")
     *
     * @SecureParam("group", permission="member")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Invite users from a list to join a group",
     *     input="Civix\ApiBundle\Form\Type\InviteType",
     *     statusCodes={
     *         204="Success",
     *         400="Bad request",
     *         403="Access Denied",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     *
     * @param Request $request
     * @param Group $group
     *
     * @return null|\Symfony\Component\Form\Form
     */
    public function putUsersAction(Request $request, Group $group)
    {
        return $this->putInvitesAction($request, $group);
    }

    /**
     * @Route("/{id}/invites", requirements={"id"="\d+"})
     * @Method("PUT")
     *
     * @SecureParam("group", permission="member")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Invite users to join a group",
     *     input="Civix\ApiBundle\Form\Type\InviteType",
     *     statusCodes={
     *         204="Success",
     *         400="Bad request",
     *         403="Access Denied",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Request $request
     * @param Group $group
     *
     * @return null|\Symfony\Component\Form\Form
     */
    public function putInvitesAction(Request $request, Group $group)
    {
        $user = $this->getUser();
        $form = $this->createForm(new InviteType($user));
        $form->submit($request);

        if ($form->isValid()) {
            if ($form->get('users')->getData()) {
                $this->manager->joinUsersByUsername(
                    $group,
                    $user,
                    $form->get('users')->getData()
                );
            } elseif ($form->get('post')->getData()) {
                $this->manager->joinUsersByPostUpvotes(
                    $group,
                    $user,
                    $form->get('post')->getData()
                );
            } elseif ($form->get('user_petition')->getData()) {
                $this->manager->joinUsersByUserPetitionSignatures(
                    $group,
                    $user,
                    $form->get('user_petition')->getData()
                );
            }
            return null;
        }

        return $form;
    }

    /**
     * Approve invite
     *
     * @Route("/{id}/invites/{user}", requirements={"id"="\d+"})
     * @Method("PATCH")
     *
     * @SecureParam("group", permission="manage")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Approve user's invite",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\UserGroup",
     *          "groups" = {"api-info"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         400="Bad request",
     *         403="Access Denied",
     *         404="Group or User Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-info"})
     *
     * @param Group $group
     * @param User $user
     *
     * @return null|UserGroup
     */
    public function patchGroupInvitesAction(Group $group, User $user)
    {
        if (!$user->getGroups()->contains($group) && $user->getInvites()->contains($group)) {
            return $this->manager->joinToGroup($user, $group);
        }

        return null;
    }

    /**
     * Reject invite
     *
     * @Route("/{id}/invites/{user}", requirements={"id"="\d+"})
     * @Method("DELETE")
     *
     * @SecureParam("group", permission="manage")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Reject user's invite",
     *     statusCodes={
     *         204="Success",
     *         400="Bad request",
     *         403="Access Denied",
     *         404="Group or User Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Group $group
     * @param User $user
     */
    public function deleteGroupInviteAction(Group $group, User $user)
    {
        $this->manager->removeInvite($group, $user);
    }

    /**
     * Add group's manager
     *
     * @Route("/{id}/managers/{user}", requirements={"id"="\d+"})
     * @Method("PUT")
     *
     * @SecureParam("group", permission="assign")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Add group's manager",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\UserGroupManager",
     *          "groups" = {"api-info"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         403="Access Denied",
     *         404="Group or User Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-info"})
     *
     * @param Group $group
     * @param User $user
     *
     * @return \Civix\CoreBundle\Entity\UserGroupManager
     */
    public function putGroupManagerAction(Group $group, User $user)
    {
        return $this->manager->addGroupManager($group, $user);
    }

    /**
     * Delete group's manager
     *
     * @Route("/{id}/managers/{user}", requirements={"id"="\d+"})
     * @Method("DELETE")
     *
     * @SecureParam("group", permission="assign")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Delete group's manager",
     *     statusCodes={
     *         204="Success",
     *         403="Access Denied",
     *         404="Group or User Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Group $group
     * @param User $user
     */
    public function deleteGroupManagerAction(Group $group, User $user)
    {
        $this->manager->deleteGroupManager($group, $user);
    }

    /**
     * Returns group's members.
     *
     * @Route("/{id}/members", defaults={"_filename" = "membership_roster.csv"})
     * @Method("GET")
     *
     * @SecureParam("group", permission="edit")
     *
     * @ApiDoc(
     *     authentication = true,
     *     resource=true,
     *     section="Groups",
     *     description="Return group's members",
     *     statusCodes={
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Group $group
     *
     * @return array
     */
    public function getMembersAction(Group $group)
    {
        $query = $this->em->getRepository(UserGroup::class)
            ->getFindMembersWithRequiredFieldsQuery($group);
        $result = $query->fetchAll();

        return $result;
    }

    /**
     * List all the responses for a given group.
     *
     * @Route("/{id}/responses")
     * @Method("GET")
     *
     * @SecureParam("group", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="List the responses for a given group.",
     *     output="array",
     *     statusCodes={
     *         200="Returns list",
     *         403="Access Denied",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Group $group
     *
     * @return array
     */
    public function getResponsesAction(Group $group)
    {
        $query = $this->em->getRepository('CivixCoreBundle:Poll\Answer')
            ->getResponsesByGroup($group);

        return $query->fetchAll();
    }
}