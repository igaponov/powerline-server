<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Form\Type\Group\LinkType;
use Civix\CoreBundle\Entity\Group;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations as REST;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/group-links/{id}")
 */
class GroupLinksController
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
     * @REST\Put("")
     *
     * @Security("is_granted('manage', link)")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Groups",
     *     description="Update group's link",
     *     input="Civix\ApiBundle\Form\Type\Group\LinkType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Group\Link",
     *          "groups" = {"group-link"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Group Link Not Found",
     *     }
     * )
     *
     * @View(serializerGroups={"group-link"})
     *
     * @param Group\Link $link
     * @param Request $request
     *
     * @return \Symfony\Component\Form\FormInterface|Group\Link
     */
    public function putAction(Request $request, Group\Link $link)
    {
        $form = $this->formFactory->create(LinkType::class, $link, ['group' => $link->getGroup()]);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            $this->em->flush();

            return $link;
        }

        return $form;
    }

    /**
     * @REST\Delete("")
     *
     * @Security("is_granted('manage', link)")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Delete group's link",
     *     statusCodes={
     *         204="Success",
     *         403="Access Denied",
     *         404="Group Link Not Found",
     *     }
     * )
     *
     * @param Group\Link $link
     */
    public function deleteAction(Group\Link $link): void
    {
        $this->em->remove($link);
        $this->em->flush();
    }
}