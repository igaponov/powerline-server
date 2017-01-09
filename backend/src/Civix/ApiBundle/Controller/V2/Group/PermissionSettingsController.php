<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\Group\PermissionSettingsType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Service\Group\GroupManager;
use FOS\RestBundle\Controller\Annotations\View;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/groups/{group}/permission-settings")
 */
class PermissionSettingsController extends Controller
{
    /**
     * @var GroupManager
     * @DI\Inject("civix_core.group_manager")
     */
    private $manager;

    /**
     * Return group's permission settings
     *
     * @Route("")
     * @Method("GET")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Return group's permission settings",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Group",
     *          "groups" = {"permission-settings"},
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
     * @View(serializerGroups={"permission-settings"})
     *
     * @param Group $group
     *
     * @return Group
     */
    public function getAction(Group $group)
    {
        return $group;
    }

    /**
     * Update group's permission settings
     *
     * @Route("")
     * @Method("PUT")
     *
     * @SecureParam("group", permission="manage")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Update group's permission settings",
     *     input="Civix\ApiBundle\Form\Type\Group\PermissionSettingsType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Group",
     *          "groups" = {"permission-settings"},
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
     * @View(serializerGroups={"permission-settings"})
     *
     * @param Request $request
     * @param Group $group
     *
     * @return Group|\Symfony\Component\Form\Form
     */
    public function putAction(Request $request, Group $group)
    {
        $form = $this->createForm(PermissionSettingsType::class, $group);
        $form->submit($request->request->all());
        
        if ($form->isValid()) {
            return $this->manager->changePermissionSettings($group);
        }

        return $form;
    }
}
