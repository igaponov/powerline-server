<?php
namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Form\Type\GroupType;
use Civix\ApiBundle\Form\Type\WorksheetType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Model\Group\Worksheet;
use Civix\CoreBundle\QueryFunction\UserGroupsQuery;
use Civix\CoreBundle\Service\Group\GroupManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Knp\Component\Pager\Event\AfterEvent;
use Knp\Component\Pager\Pagination\AbstractPagination;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
     * @var EntityManagerInterface
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

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
     *     output = {
     *          "class" = "array<Civix\CoreBundle\Entity\UserGroup> as paginator",
     *          "groups" = {"api-groups", "group-list"},
     *          "parsers" = {
     *              "Civix\ApiBundle\Parser\PaginatorParser"
     *          }
     *     },
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
     * @return PaginationInterface
     */
    public function getGroupsAction(ParamFetcher $params): PaginationInterface
    {
        $user = $this->getUser();
        $query = new UserGroupsQuery($this->em);
        $paginator = $this->get('knp_paginator');
        $paginator->connect('knp_pager.after', function (AfterEvent $event) use ($query, $user) {
            $pagination = $event->getPaginationView();
            if (!$pagination instanceof AbstractPagination) {
                return;
            }
            $query->runPostQueries($user, ...$pagination->getItems());
        });

        return $paginator->paginate(
            $query($user),
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
     *              "groups" = {"api-info", "permission-settings"},
     *              "parsers" = {
     *                  "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *              }
     *          }
     *     }
     * )
     *
     * @View(serializerGroups={"api-info", "permission-settings"}, statusCode=201)
     *
     * @param Request $request
     *
     * @return Group|\Symfony\Component\Form\Form
     */
    public function postAction(Request $request)
    {
        $form = $this->createForm(GroupType::class, null, [
            'validation_groups' => 'user-registration',
        ]);
        $form->submit($request->request->all(), false);

        if ($form->isValid()) {
            /** @var Group $group */
            $group = $form->getData();
            $group->setOwner($this->getUser());
            $this->manager->create($group);
            $this->manager->joinToGroup($this->getUser(), $group);

            return $group;
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
        $form = $this->createForm(WorksheetType::class, new Worksheet($this->getUser(), $group));
        $form->submit($request->request->all());

        if ($form->isValid()) {
            try {
                return $this->manager->inquire($form->getData());
            } catch (\DomainException $e) {
                throw new BadRequestHttpException($e->getMessage());
            }
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