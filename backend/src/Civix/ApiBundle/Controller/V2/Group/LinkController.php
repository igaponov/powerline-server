<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Form\Type\Group\LinkType;
use Civix\CoreBundle\Entity\Group;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations as REST;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/groups/{group}/links")
 */
class LinkController
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    public function __construct(EntityManager $em, FormFactoryInterface $formFactory)
    {
        $this->em = $em;
        $this->formFactory = $formFactory;
    }

    /**
     * @REST\Get("")
     *
     * @Security("is_granted('member', group)")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Return group's links",
     *     output={
     *          "class" = "array<Civix\CoreBundle\Entity\Group\Link>",
     *          "groups" = {"group-link"},
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
     * @View(serializerGroups={"group-link"})
     *
     * @param Group $group
     *
     * @return Collection
     */
    public function getLinksAction(Group $group): Collection
    {
        return $group->getLinks();
    }

    /**
     * Add groups's link
     *
     * @REST\Post("")
     *
     * @Security("is_granted('manage', group)")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Add group's link",
     *     input="Civix\ApiBundle\Form\Type\Group\LinkType",
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Group Not Found",
     *     },
     *     responseMap={
     *          201={
     *              "class" = "Civix\CoreBundle\Entity\Group\Link",
     *              "groups" = {"group-link"},
     *              "parsers" = {
     *                  "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *              }
     *          }
     *     }
     * )
     *
     * @View(serializerGroups={"group-link"}, statusCode=201)
     *
     * @param Request $request
     * @param Group $group
     *
     * @return Group\Link|\Symfony\Component\Form\FormInterface
     */
    public function postAction(Request $request, Group $group)
    {
        $form = $this->formFactory->create(LinkType::class, null, compact('group'));
        $form->submit($request->request->all());

        if ($form->isValid()) {
            /** @var Group\Link $link */
            $link = $form->getData();
            $group->addLink($link);
            $this->em->flush();

            return $link;
        }

        return $form;
    }
}
