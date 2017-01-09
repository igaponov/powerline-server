<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\MicropetitionConfigType;
use Civix\CoreBundle\Entity\Group;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/groups/{group}/micro-petitions-config")
 */
class MicropetitionConfigController extends Controller
{
    /**
     * Return micropetition's config
     *
     * @Route("")
     * @Method("GET")
     *
     * @SecureParam("group", permission="view")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Return micropetitions's config",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Group",
     *          "groups" = {"micropetition-config"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         403="Access Denied",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"micropetition-config"})
     *
     * @param Group $group
     *
     * @return Group
     */
    public function getConfigAction(Group $group)
    {
        return $group;
    }

    /**
     * Update micropetition's config
     *
     * @Route("")
     * @Method("PUT")
     *
     * @SecureParam("group", permission="micropetition_config")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Update micropetitions's config",
     *     input="Civix\ApiBundle\Form\Type\MicropetitionConfigType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Group",
     *          "groups" = {"micropetition-config"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"micropetition-config"})
     *
     * @param Request $request
     * @param Group $group
     *
     * @return Group|\Symfony\Component\Form\Form
     */
    public function putConfigAction(Request $request, Group $group)
    {
        $form = $this->createForm(MicropetitionConfigType::class, $group, [
            'validation_groups' => ['micropetition-config'],
        ]);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($group);
            $entityManager->flush();

            return $group;
        }

        return $form;
    }
}
