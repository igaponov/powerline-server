<?php
namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Form\Type\RepresentativeType;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Service\Representative\RepresentativeManager;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/user/representatives")
 */
class UserRepresentativeController extends FOSRestController
{
    /**
     * @var RepresentativeManager
     * @DI\Inject("civix_core.representative_manager")
     */
    private $manager;

    /**
     * List the authenticated user's representatives
     *
     * @Route("")
     * @Method("GET")
     *
     * @QueryParam(name="page", requirements="\d+", default="1")
     * @QueryParam(name="per_page", requirements="(10|20|50)", default="50")
     *
     * @ApiDoc(
     *     authentication = true,
     *     resource=true,
     *     section="Representatives",
     *     description="List representatives of a user",
     *     output = {
     *          "class" = "array<Civix\CoreBundle\Entity\Representative> as paginator",
     *          "groups" = {"api-info"},
     *          "parsers" = {
     *              "Civix\ApiBundle\Parser\PaginatorParser"
     *          }
     *     },
     *     statusCodes={
     *          405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api-info"})
     *
     * @param ParamFetcher $params
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getRepresentativesAction(ParamFetcher $params)
    {
        $query = $this->getDoctrine()->getRepository(Representative::class)
            ->getByUserQuery($this->getUser());

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }

    /**
     * @Route("")
     * @Method("POST")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Representatives",
     *     description="Create a representative",
     *     input="Civix\ApiBundle\Form\Type\RepresentativeType",
     *     statusCodes={
     *         400="Bad Request",
     *         405="Method Not Allowed"
     *     },
     *     responseMap={
     *          201 = {
     *              "class" = "Civix\CoreBundle\Entity\Representative",
     *              "groups" = {"api-info"},
     *              "parsers" = {
     *                  "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *              }
     *          }
     *     }
     * )
     *
     * @View(serializerGroups={"api-info"}, statusCode=201)
     *
     * @param Request $request
     *
     * @return Representative|\Symfony\Component\Form\Form
     */
    public function postAction(Request $request)
    {
        $representative = new Representative($this->getUser());
        $form = $this->createForm(
            RepresentativeType::class,
            $representative, [
                'validation_groups' => 'registration',
            ]
        );
        $form->submit($request->request->all());

        if ($form->isValid()) {
            return $this->manager->save($representative);
        }

        return $form;
    }
}