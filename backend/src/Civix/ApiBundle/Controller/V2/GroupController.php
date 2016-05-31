<?php
namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\GroupType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/groups")
 */
class GroupController extends FOSRestController
{
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
     *
     * @ApiDoc(
     *     authentication = true,
     *     section="Group",
     *     description="Return groups",
     *     output="Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *     statusCodes={
     *         200="Returns groups",
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
        $query = $this->getDoctrine()->getRepository(Group::class)
            ->getFindByQuery([
                'exclude_owned' => $params->get('exclude_owned') ? $this->getUser() : null,
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
     * @ParamConverter("group")
     *
     * @ApiDoc(
     *     authentication = true,
     *     resource=true,
     *     section="Group",
     *     description="Returns a group",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\Group",
     *          "groups" = {"api-info"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *          200="Returned when successful",
     *          405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-info"})
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
     * Adds an user's group
     *
     * @Route("")
     * @Method("POST")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Group",
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
     *         201="Returns a new group",
     *         400="Bad Request",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-info"})
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View|\Symfony\Component\Form\Form
     */
    public function postAction(Request $request)
    {
        $groupManager = $this->get('civix_core.group_manager');
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getGroups()->count() > 4) {
            throw new AccessDeniedException('You have reached a limit for creating groups');
        }

        $form = $this->createForm(new GroupType(), null, [
            'validation_groups' => 'user-registration',
        ]);
        $form->submit($request);

        if ($form->isValid()) {
            /** @var Group $group */
            $group = $form->getData();
            $group->setOwner($this->getUser());
            $groupManager->create($group);
            $groupManager->joinToGroup($user, $group);

            return $this->view($group, 201);
        }

        return $form;
    }

    /**
     * Updates an user's group
     *
     * @Route("/{id}")
     * @Method("PUT")
     *
     * @ParamConverter("group")
     * 
     * @SecureParam("group", permission="edit")
     * 
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Group",
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
     *         200="Returns a group",
     *         400="Bad Request",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-info"})
     *
     * @param Request $request
     * @param Group $group
     *
     * @return \FOS\RestBundle\View\View|\Symfony\Component\Form\Form
     */
    public function putAction(Request $request, Group $group)
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $form = $this->createForm(new GroupType(), $group, [
            'validation_groups' => 'user-registration',
        ]);
        $form->submit($request, false);

        if ($form->isValid()) {
            $em->persist($group);
            $em->flush();

            return $group;
        }

        return $form;
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
     *     resource=true,
     *     section="Group",
     *     description="Returns a list of users from a group",
     *     output="Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *     statusCodes={
     *         200="Returns users",
     *         400="Bad request",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api-short-info"})
     *
     * @param ParamFetcher $params
     * @param $group
     *
     * @return Response
     */
    public function getUsersAction(ParamFetcher $params, Group $group)
    {
        $query = $this->getDoctrine()->getRepository(User::class)
            ->getFindByGroupQuery($group);

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }
}