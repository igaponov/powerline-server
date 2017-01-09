<?php
namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\Group\GroupSectionType;
use Civix\CoreBundle\Entity\GroupSection;
use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/group-sections")
 */
class GroupSectionController extends FOSRestController
{
    /**
     * @var EntityManager
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

    /**
     * Update groups's section
     *
     * @Route("/{id}")
     * @Method("PUT")
     *
     * @SecureParam("section", permission="manage")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Groups",
     *     description="Update group's section",
     *     input="Civix\ApiBundle\Form\Type\Group\GroupSectionType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Group\GroupSection",
     *          "groups" = {"group-section"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Group Field Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"group-section"})
     *
     * @param GroupSection $section
     * @param Request $request
     *
     * @return \Symfony\Component\Form\Form
     */
    public function putGroupSectionAction(GroupSection $section, Request $request)
    {
        $form = $this->createForm(GroupSectionType::class, $section);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            $section = $form->getData();
            $this->em->persist($section);
            $this->em->flush();

            return $section;
        }

        return $form;
    }

    /**
     * Delete groups's section
     *
     * @Route("/{id}")
     * @Method("DELETE")
     *
     * @SecureParam("section", permission="manage")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Delete group's section",
     *     statusCodes={
     *         204="Success",
     *         403="Access Denied",
     *         404="Group Field Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param GroupSection $section
     */
    public function deleteGroupSectionAction(GroupSection $section)
    {
        $this->em->remove($section);
        $this->em->flush();
    }

    /**
     * Return group sections's users
     *
     * @Route("/{id}/users")
     * @Method("GET")
     *
     * @QueryParam(name="page", requirements="\d+", default="1")
     * @QueryParam(name="per_page", requirements="(10|20)", default="20")
     *
     * @SecureParam("section", permission="member")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Return group sections's users",
     *     output={
     *          "class" = "array<Civix\CoreBundle\Entity\User> as paginator",
     *          "groups" = {"user-list"},
     *          "parsers" = {
     *              "Civix\ApiBundle\Parser\PaginatorParser"
     *          }
     *     },
     *     statusCodes={
     *         403="Access Denied",
     *         404="Group Section Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "user-list"})
     *
     * @param ParamFetcher $params
     * @param GroupSection $section
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getGroupSectionsUsersAction(ParamFetcher $params, GroupSection $section)
    {
        $query = $this->em->getRepository(User::class)
            ->getFindByGroupSectionQuery($section);

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }

    /**
     * Add groups section's user
     *
     * @Route("/{id}/users/{user}")
     * @Method("PUT")
     *
     * @SecureParam("section", permission="manage")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Add group section's user",
     *     statusCodes={
     *         403="Access Denied",
     *         404="Group or User Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param GroupSection $section
     * @param User $user
     */
    public function putGroupSectionUsersAction(GroupSection $section, User $user)
    {
        if (!$section->getUsers()->contains($user)) {
            $section->addUser($user);
            $this->em->persist($section);
            $this->em->flush();
        }
    }

    /**
     * Delete groups section's user
     *
     * @Route("/{id}/users/{user}")
     * @Method("DELETE")
     *
     * @SecureParam("section", permission="manage")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Delete group section's user",
     *     statusCodes={
     *         403="Access Denied",
     *         404="Group or User Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param GroupSection $section
     * @param User $user
     */
    public function deleteGroupSectionUsersAction(GroupSection $section, User $user)
    {
        if ($section->getUsers()->contains($user)) {
            $section->removeUser($user);
            $this->em->persist($section);
            $this->em->flush();
        }
    }
}