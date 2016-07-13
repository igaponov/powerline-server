<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\Group\GroupFieldType;
use Civix\CoreBundle\Entity\Group;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/group-fields")
 */
class GroupFieldsController extends FOSRestController
{
    /**
     * Update groups's field
     *
     * @Route("/{id}")
     * @Method("PUT")
     *
     * @SecureParam("field", permission="manage")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Groups",
     *     description="Update group's field",
     *     input="Civix\ApiBundle\Form\Type\Group\GroupFieldType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Group\GroupField",
     *          "groups" = {"api-group-field"},
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
     * @View(serializerGroups={"api-group-field"})
     *
     * @param Group\GroupField $field
     * @param Request $request
     *
     * @return \Symfony\Component\Form\Form
     */
    public function putAction(Group\GroupField $field, Request $request)
    {
        $form = $this->createForm(new GroupFieldType(), $field);

        $form->submit($request);

        if ($form->isValid()) {
            $field = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($field);
            $em->flush();

            return $field;
        }

        return $form;
    }

    /**
     * Delete groups's field
     *
     * @Route("/{id}")
     * @Method("DELETE")
     *
     * @SecureParam("field", permission="manage")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Delete group's field",
     *     statusCodes={
     *         204="Success",
     *         403="Access Denied",
     *         404="Group Field Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Group\GroupField $field
     */
    public function deleteAction(Group\GroupField $field)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($field);
        $em->flush();
    }
}