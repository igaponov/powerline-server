<?php

namespace Civix\ApiBundle\Controller\Group;

use Civix\ApiBundle\Form\Type\Group\PermissionSettingsType;
use Civix\CoreBundle\Entity\Group;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/permission-settings")
 */
class PermissionSettingsController extends Controller
{
    /**
     * Return group's permission settings
     *
     * @Route("", name="civix_get_group_permission_settings")
     * @Method("GET")
     *
     * @ApiDoc(
     *     section="User Management",
     *     description="Return group's permission settings",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Group",
     *          "groups" = {"permission-settings"}
     *     }
     * )
     *
     * @View(serializerGroups={"permission-settings"})
     *
     * @return Group|\Symfony\Component\Form\Form
     */
    public function getAction()
    {
        return $this->getUser();
    }
    
    /**
     * Update group's permission settings
     *
     * @Route("", name="civix_put_group_permission_settings")
     * @Method("PUT")
     *
     * @ApiDoc(
     *     section="User Management",
     *     description="Update group's permission settings",
     *     input="Civix\ApiBundle\Form\Type\Group\PermissionSettingsType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Group",
     *          "groups" = {"permission-settings"}
     *     }
     * )
     *
     * @View(serializerGroups={"permission-settings"})
     *
     * @param Request $request
     * @return Group|\Symfony\Component\Form\Form
     */
    public function putAction(Request $request)
    {
        /** @var Group $group */
        $group = $this->getUser();
        $form = $this->createForm(new PermissionSettingsType(), $group);
        
        $form->submit($request);
        
        if ($form->isValid()) {
            return $this->get('civix_core.group_manager')->changePermissionSettings($group);
        }

        return $form;
    }
}
