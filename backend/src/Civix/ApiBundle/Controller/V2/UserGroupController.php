<?php
namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Form\Type\GroupType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/user/groups")
 */
class UserGroupController extends FOSRestController
{
    /**
     * Return current user's groups
     *
     * @Route("")
     * @Method("GET")
     *
     * @QueryParam(name="page", requirements="\d+", default="1")
     * @QueryParam(name="per_page", requirements="(10|20)", default="20")
     *
     * @ApiDoc(
     *     authentication = true,
     *     resource=true,
     *     section="User Management",
     *     output = "Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *     description="Return user's groups",
     *     statusCodes={
     *          200="Returned when successful",
     *          405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api-groups"})
     *
     * @param ParamFetcher $params
     * 
     * @return Response
     */
    public function getcAction(ParamFetcher $params)
    {
        $query = $this->getDoctrine()->getRepository(Group::class)
            ->getByUserQuery($this->getUser());

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
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
}