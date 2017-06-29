<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Form\Type\Group\TagType;
use Civix\CoreBundle\Entity\Group;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations as REST;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Route("/group-tags")
 */
class GroupTagsController
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
     * @REST\QueryParam(name="name", requirements=".+", description="Filter by name")
     * @REST\QueryParam(name="limit", requirements=@Assert\Range(min=5, max=20), description="Limit, min 5, max 20", default=10)
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Groups",
     *     description="Search group tags",
     *     output={
     *          "class" = "array<Civix\CoreBundle\Entity\Group\Tag>",
     *          "groups" = {"group-tag"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     }
     * )
     *
     * @View(serializerGroups={"group-tag"})
     *
     * @param ParamFetcher $params
     *
     * @return Group\Tag[]
     */
    public function getTagsAction(ParamFetcher $params): array
    {
        return $this->em->getRepository(Group\Tag::class)
            ->findByName($params->get('name'), (int)$params->get('limit'));
    }

    /**
     * @REST\Post("")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Groups",
     *     description="Add group's tag",
     *     input="Civix\ApiBundle\Form\Type\Group\TagType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Group\Tag",
     *          "groups" = {"group-tag"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *     }
     * )
     *
     * @View(serializerGroups={"group-tag"}, statusCode=201)
     *
     * @param Request $request
     *
     * @return \Symfony\Component\Form\FormInterface|Group\Tag
     */
    public function postTagAction(Request $request)
    {
        $form = $this->formFactory->create(TagType::class);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            $tag = $form->getData();
            $this->em->persist($tag);
            $this->em->flush();

            return $tag;
        }

        return $form;
    }
}