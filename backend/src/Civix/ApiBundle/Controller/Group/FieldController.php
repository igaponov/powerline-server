<?php

namespace Civix\ApiBundle\Controller\Group;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\Group\GroupFieldType;
use Civix\CoreBundle\Entity\Group;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/fields")
 */
class FieldController extends Controller
{
    /**
     * Return group's fields
     *
     * @Route("", name="civix_get_group_fields")
     * @Method("GET")
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
     * @return Group
     */
    public function getcAction()
    {
        /** @var Group $group */
        $group = $this->getUser();
        
        return $group->getFields();
    }

    /**
     * Update groups's field
     *
     * @Route("/{id}", name="civix_put_group_field")
     * @Method("PUT")
     * @SecureParam("field", permission="edit")
     *
     * @ParamConverter("field")
     *
     * @ApiDoc(
     *     section="User Management",
     *     description="Update group's field",
     *     input="Civix\ApiBundle\Form\Type\Group\GroupFieldType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Group\GroupField",
     *          "groups" = {"api-group-field"}
     *     }
     * )
     *
     * @View(serializerGroups={"api-group-field"})
     *
     * @param Group\GroupField $field
     * @param Request $request
     * @return \Symfony\Component\Form\Form
     */
    public function putAction(Group\GroupField $field, Request $request)
    {
        $form = $this->createForm(new GroupFieldType(), $field);

        $form->submit($request);

        if ($form->isValid()) {
            $field = $form->getData();
            $em = $this->get('doctrine.orm.entity_manager');
            $em->persist($field);
            $em->flush();

            return $field;
        }

        return $form;
    }

    /**
     * Add groups's field
     *
     * @Route("", name="civix_post_group_field")
     * @Method("POST")
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
     * @return \Symfony\Component\Form\Form
     */
    public function postAction(Request $request)
    {
        $form = $this->createForm(new GroupFieldType());

        $form->submit($request);

        if ($form->isValid()) {
            $field = $form->getData();
            $em = $this->get('doctrine.orm.entity_manager');
            $em->persist($field);
            $em->flush();

            return $field;
        }

        return $form;
    }

    /**
     * Delete groups's field
     *
     * @Route("/{id}", name="civix_delete_group_field")
     * @Method("DELETE")
     * @SecureParam("field", permission="delete")
     *
     * @ApiDoc(
     *     section="User Management",
     *     description="Delete group's field"
     * )
     * 
     * @param Group\GroupField $field
     */
    public function deleteAction(Group\GroupField $field)
    {
        /** @var Group $group */
        $group = $this->getUser();
        $group->removeField($field);
        $em = $this->get('doctrine.orm.entity_manager');
        $em->persist($group);
        $em->flush();    
    }
}
