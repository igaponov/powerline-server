<?php
namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\Group\GroupSectionType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\GroupSection;
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
 * @Route("/groups/{group}/sections")
 */
class SectionsController extends FOSRestController
{
    /**
     * @var EntityManager
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

    /**
     * Return group's sections
     *
     * @Route("")
     * @Method("GET")
     *
     * @QueryParam(name="page", requirements="\d+", default="1")
     * @QueryParam(name="per_page", requirements="(10|20)", default="20")
     *
     * @SecureParam("group", permission="member")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Return group's sections",
     *     output={
     *          "class" = "array<Civix\CoreBundle\Entity\GroupSection> as paginator",
     *          "groups" = {"group-section"},
     *          "parsers" = {
     *              "Civix\ApiBundle\Parser\PaginatorParser"
     *          }
     *     },
     *     statusCodes={
     *         403="Access Denied",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "group-section"})
     *
     * @param ParamFetcher $params
     * @param Group $group
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getGroupSectionsAction(ParamFetcher $params, Group $group)
    {
        $query = $this->em->getRepository(GroupSection::class)
            ->getFindByGroupQuery($group);

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }

    /**
     * Add groups's section
     *
     * @Route("")
     * @Method("POST")
     *
     * @SecureParam("group", permission="manage")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Add group's field",
     *     input="Civix\ApiBundle\Form\Type\Group\GroupSectionType",
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     },
     *     responseMap={
     *          201={
     *              "class" = "Civix\CoreBundle\Entity\Group\GroupField",
     *              "groups" = {"group-section"},
     *              "parsers" = {
     *                  "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *              }
     *          }
     *     }
     * )
     *
     * @View(serializerGroups={"group-section"}, statusCode=201)
     *
     * @param Request $request
     * @param Group $group
     *
     * @return GroupSection|\Symfony\Component\Form\Form
     */
    public function postGroupSectionAction(Request $request, Group $group)
    {
        $form = $this->createForm(GroupSectionType::class);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            /** @var GroupSection $section */
            $section = $form->getData();
            $section->setGroup($group);
            $em = $this->getDoctrine()->getManager();
            $em->persist($section);
            $em->flush();

            return $section;
        }

        return $form;
    }
}