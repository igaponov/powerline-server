<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Service\Group\GroupManager;
use Doctrine\Common\Collections\Collection;
use FOS\RestBundle\Controller\Annotations as REST;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/groups/{group}/tags")
 */
class TagController
{
    /**
     * @var GroupManager
     */
    private $manager;

    public function __construct(GroupManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @REST\Get("")
     *
     * @Security("is_granted('member', group)")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Return group's tags",
     *     output={
     *          "class" = "array<Civix\CoreBundle\Entity\Group\Tag>",
     *          "groups" = {"group-tag"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\CollectionParser",
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         403="Access Denied",
     *         404="Group Not Found",
     *     }
     * )
     *
     * @View(serializerGroups={"group-tag"})
     *
     * @param Group $group
     *
     * @return Collection
     */
    public function getTagsAction(Group $group): Collection
    {
        return $group->getTags();
    }

    /**
     * @REST\Put("/{id}")
     *
     * @Security("is_granted('manage', group)")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Add group's tag",
     *     statusCodes={
     *         204="Success",
     *         403="Access Denied",
     *         404="Group Not Found",
     *     }
     * )
     *
     * @param Group $group
     * @param Group\Tag $tag
     */
    public function putAction(Group $group, Group\Tag $tag): void
    {
        $this->manager->addTag($group, $tag);
    }

    /**
     * @REST\Delete("/{id}")
     *
     * @Security("is_granted('manage', group)")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Delete group's tag",
     *     statusCodes={
     *         204="Success",
     *         403="Access Denied",
     *         404="Group Not Found",
     *     }
     * )
     *
     * @param Group $group
     * @param Group\Tag $tag
     */
    public function deleteAction(Group $group, Group\Tag $tag): void
    {
        $this->manager->removeTag($group, $tag);
    }
}
