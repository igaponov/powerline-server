<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\Group\GroupFieldType;
use Civix\CoreBundle\Entity\Group;
use Doctrine\Common\Collections\Collection;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
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
     *     section="User Management",
     *     description="Return group's fields",
     *     output={
     *          "class" = "ArrayCollection<Civix\CoreBundle\Entity\Group\GroupField>",
     *          "groups" = {"api-groups-fields"}
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
     * @Route("", name="civix_post_group_field")
     * @Method("POST")
     *
     * @SecureParam("group", permission="manage")
     *
     * @ApiDoc(
     *     section="User Management",
     *     description="Add group's field",
     *     input="Civix\ApiBundle\Form\Type\Group\GroupFieldType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Group\GroupField",
     *          "groups" = {"api-group-field"}
     *     }
     * )
     *
     * @View(serializerGroups={"api-group-field"})
     *
     * @param Request $request
     * @param Group $group
     *
     * @return Group\GroupField|\Symfony\Component\Form\Form
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

            return $field;
        }

        return $form;
    }
}
