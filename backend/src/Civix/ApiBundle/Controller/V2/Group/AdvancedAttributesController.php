<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Form\Type\Group\AdvancedAttributesType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Service\Group\GroupManager;
use FOS\RestBundle\Controller\Annotations as REST;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/groups/{group}/advanced-attributes", requirements={"group"="\d+"})
 */
class AdvancedAttributesController extends FOSRestController
{
    /**
     * @var GroupManager
     * @DI\Inject("civix_core.group_manager")
     */
    private $manager;

    /**
     * Get group's advanced attributes
     *
     * @REST\Get("")
     *
     * @Security(expression="is_granted('member', group)")
     *
     * @ApiDoc(
     *     resource=true,
     *     authentication=true,
     *     section="Groups",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\Group\AdvancedAttributes",
     *          "groups" = {"Default"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *          403="User is not a member of the group",
     *          404="Group not found",
     *     }
     * )
     *
     * @REST\View(serializerGroups={"Default"})
     *
     * @param Group $group
     *
     * @return Group\AdvancedAttributes|\Symfony\Component\Form\Form
     */
    public function getAdvancedAttributesAction(Group $group)
    {
        return $group->getAdvancedAttributes();
    }

    /**
     * Update group's advanced attributes
     *
     * @REST\Put("")
     *
     * @Security(expression="is_granted('advanced_attributes', group)")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     input="Civix\ApiBundle\Form\Type\Group\AdvancedAttributesType",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\Group\AdvancedAttributes",
     *          "groups" = {"Default"},
     *          "parsers" = {"Nelmio\ApiDocBundle\Parser\JmsMetadataParser"}
     *     },
     *     statusCodes={
     *          403={
     *              "User is not a manager of the group",
     *              "Group has a FREE subscription"
     *          },
     *          404="Group not found",
     *     }
     * )
     *
     * @REST\View(serializerGroups={"Default"})
     *
     * @param Request $request
     * @param Group $group
     *
     * @return Group\AdvancedAttributes|\Symfony\Component\Form\Form
     */
    public function putAdvancedAttributesAction(Request $request, Group $group)
    {
        $advancedAttributes = $group->getAdvancedAttributes();
        $form = $this->createForm(AdvancedAttributesType::class, $advancedAttributes);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            return $this->manager->save($group)->getAdvancedAttributes();
        }

        return $form;
    }
}