<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Form\Type\GroupBannerType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Service\AvatarManager;
use Civix\CoreBundle\Service\Group\GroupManager;
use FOS\RestBundle\Controller\Annotations as REST;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/groups/{group}/banner")
 */
class BannerController
{
    /**
     * @var GroupManager
     */
    private $groupManager;
    /**
     * @var AvatarManager
     */
    private $avatarManager;
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    public function __construct(
        GroupManager $groupManager,
        AvatarManager $avatarManager,
        FormFactoryInterface $formFactory
    ) {
        $this->groupManager = $groupManager;
        $this->avatarManager = $avatarManager;
        $this->formFactory = $formFactory;
    }

    /**
     * @REST\Put("")
     *
     * @Security("is_granted('edit', group)")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Updates group's banner",
     *     input="Civix\ApiBundle\Form\Type\GroupBannerType",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\Group",
     *          "groups" = {"api-info"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         404="Group Not Found"
     *     }
     * )
     *
     * @View(serializerGroups={"api-info"})
     *
     * @param Request $request
     * @param Group $group
     *
     * @return Group|FormInterface
     */
    public function putAction(Request $request, Group $group)
    {
        $form = $this->formFactory->create(GroupBannerType::class, $group);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            return $this->groupManager->save($group);
        }

        return $form;
    }

    /**
     * @REST\Delete("")
     *
     * @Security("is_granted('edit', group)")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Deletes group's banner",
     *     statusCodes={
     *         204="Success",
     *         404="Group Not Found"
     *     }
     * )
     *
     * @param Group $group
     */
    public function deleteAction(Group $group): void
    {
        $this->avatarManager->deleteAvatar($group);
    }
}