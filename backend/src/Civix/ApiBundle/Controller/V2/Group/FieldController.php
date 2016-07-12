<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\Group\GroupFieldType;
use Civix\CoreBundle\Entity\Group;
use Doctrine\Common\Collections\Collection;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/groups/{group}/fields")
 */
class FieldController extends FOSRestController
{
    /**
     * Return group's fields
     *
     * @Route("")
     * @Method("GET")
     *
     * @SecureParam("group", permission="view")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Return group's fields",
     *     output={
     *          "class" = "array<Civix\CoreBundle\Entity\Group\GroupField>",
     *          "groups" = {"api-groups-fields"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         403="Access Denied",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-groups-fields"})
     *
     * @param Group $group
     *
     * @return Collection
     */
    public function getcAction(Group $group)
    {
        return $group->getFields();
    }

    /**
     * Add groups's field
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
     *     input="Civix\ApiBundle\Form\Type\Group\GroupFieldType",
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     },
     *     responseMap={
     *          201={
     *              "class" = "Civix\CoreBundle\Entity\Group\GroupField",
     *              "groups" = {"api-group-field"},
     *              "parsers" = {
     *                  "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *              }
     *          }
     *     }
     * )
     *
     * @View(serializerGroups={"api-group-field"})
     *
     * @param Request $request
     * @param Group $group
     *
     * @return \FOS\RestBundle\View\View|\Symfony\Component\Form\Form
     */
    public function postAction(Request $request, Group $group)
    {
        $form = $this->createForm(new GroupFieldType());

        $form->submit($request);

        if ($form->isValid()) {
            /** @var Group\GroupField $field */
            $field = $form->getData();
            $field->setGroup($group);
            $em = $this->getDoctrine()->getManager();
            $em->persist($field);
            $em->flush();

            return $this->view($field, Codes::HTTP_CREATED);
        }

        return $form;
    }
}
