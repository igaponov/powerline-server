<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Form\Type\UserGroupPermissionType;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Civix\CoreBundle\Entity\UserGroup;

/**
 * @Route("/groups/{group}/permissions")
 */
class PermissionController extends FOSRestController
{
    /**
     * @var EntityManager
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

    /**
     * @Route("")
     * @Method("GET")
     *
     * @ParamConverter("userGroup", options={"mapping" = {"loggedInUser" = "user", "group" = "group"}}, converter="doctrine.param_converter")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Return user's permissions for a group",
     *     output={
     *          "class" = "\Civix\CoreBundle\Entity\UserGroup",
     *          "groups" = {"api-permissions"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-permissions"})
     *
     * @param UserGroup $userGroup
     *
     * @return UserGroup
     */
    public function getAction(UserGroup $userGroup)
    {
        return $userGroup;
    }

    /**
     * @Route("")
     * @Method("PUT")
     *
     * @ParamConverter("userGroup", options={"mapping" = {"loggedInUser" = "user", "group" = "group"}}, converter="doctrine.param_converter")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Update user's permissions for a group",
     *     input="\Civix\ApiBundle\Form\Type\UserGroupPermissionType",
     *     output={
     *          "class" = "\Civix\CoreBundle\Entity\UserGroup",
     *          "groups" = {"api-permissions"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-permissions"})
     *
     * @param UserGroup $userGroup
     * @param Request $request
     *
     * @return UserGroup|\Symfony\Component\Form\Form
     */
    public function putAction(UserGroup $userGroup, Request $request)
    {
        $form = $this->createForm(new UserGroupPermissionType(), $userGroup);
        $form->submit($request, false);

        if ($form->isValid()) {
            $userGroup->setPermissionsApprovedAt(new \DateTime());
            $this->em->persist($userGroup);
            $this->em->flush();

            return $userGroup;
        }

        return $form;
    }
}
