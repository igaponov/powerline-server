<?php
namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Form\Type\GroupType;
use Civix\ApiBundle\Form\Type\WorksheetType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Model\Group\Worksheet;
use Civix\CoreBundle\Service\Group\GroupManager;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/user/groups")
 */
class UserGroupController extends FOSRestController
{
    /**
     * @var GroupManager
     * @DI\Inject("civix_core.group_manager")
     */
    private $manager;

    /**
     * List the authenticated user's groups
     *
     * @Route("")
     * @Method("GET")
     *
     * @QueryParam(name="page", requirements="\d+", default="1")
     * @QueryParam(name="per_page", requirements="(10|20|50)", default="50")
     *
     * @ApiDoc(
     *     authentication = true,
     *     resource=true,
     *     section="Groups",
     *     description="List groups of a user",
     *     output = "Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *     description="Return user's groups",
     *     statusCodes={
     *          405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api-groups", "group-list"})
     *
     * @param ParamFetcher $params
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
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
     * @Route("")
     * @Method("POST")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Create a group",
     *     input="Civix\ApiBundle\Form\Type\GroupType",
     *     statusCodes={
     *         400="Bad Request",
     *         405="Method Not Allowed"
     *     },
     *     responseMap={
     *          201 = {
     *              "class" = "Civix\CoreBundle\Entity\Group",
     *              "groups" = {"api-info"},
     *              "parsers" = {
     *                  "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *              }
     *          }
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
        $form = $this->createForm(new GroupType(), null, [
            'validation_groups' => 'user-registration',
        ]);
        $form->submit($request, false);

        if ($form->isValid()) {
            /** @var Group $group */
            $group = $form->getData();
            $group->setOwner($this->getUser());
            $this->manager->create($group);
            $this->manager->joinToGroup($this->getUser(), $group);

            return $this->view($group, 201);
        }

        return $form;
    }

    /**
     * @Route("/{id}")
     * @Method("PUT")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Join a group",
     *     input="Civix\ApiBundle\Form\Type\WorksheetType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\UserGroup",
     *          "groups" = {"api-info"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
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
     * @return Form|UserGroup
     */
    public function putAction(Request $request, Group $group)
    {
        $form = $this->createForm(new WorksheetType(), new Worksheet($this->getUser(), $group));
        $form->submit($request);

        if ($form->isValid()) {
            return $this->manager->inquire($form->getData());
        }

        return $form;
    }

    /**
     * @Route("/{id}")
     * @Method("DELETE")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Unjoin a group",
     *     statusCodes={
     *         204="Success",
     *         403="Access Denied",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Group $group
     */
    public function deleteAction(Group $group)
    {
        $this->manager->unjoinGroup($this->getUser(), $group);
    }
}