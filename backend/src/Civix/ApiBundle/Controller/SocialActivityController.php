<?php

namespace Civix\ApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Civix\CoreBundle\Entity\SocialActivity;

/**
 * @Route("/social-activities")
 */
class SocialActivityController extends BaseController
{
    /**
     * List all social activities for a current user.
     * Deprecated, use `GET /api/v2/user/social-activities` instead.
     *
     * @Route("/", name="civix_get_social_activities")
     * @Method("GET")
     * @QueryParam(name="page", requirements="\d+", default=1)
     * @QueryParam(name="limit", requirements="\d+", default=20)
     *
     * @ApiDoc(
     *     resource=true,
     *     section="Social Activity",
     *     description="List all social activities for a current user.",
     *     output="Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *     statusCodes={
     *         200="Returns list",
     *         400="Bad Request",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     *
     * @View(serializerGroups={"paginator", "api-activities"})
     *
     * @param ParamFetcher $params
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function listAction(ParamFetcher $params)
    {
        $query = $this->getDoctrine()
            ->getRepository(SocialActivity::class)
            ->getFilteredByFollowingAndRecipientQuery($this->getUser());
        $paginator = $this->get('knp_paginator');
        return $paginator->paginate(
            $query,
            $params->get('page'),
            $params->get('limit')
        );
    }

    /**
     * Deprecated, use `DELETE /api/v2/social-activities` instead.
     *
     * @Route("/{id}")
     * @Method("DELETE")
     *
     * @ApiDoc(
     *     section="Social Activity",
     *     deprecated=true
     * )
     */
    public function removeAction(SocialActivity $socialActivity)
    {
        if ($this->getUser() !== $socialActivity->getRecipient()) {
            throw $this->createNotFoundException();
        }
        $this->getDoctrine()->getManager()->remove($socialActivity);
        $this->getDoctrine()->getManager()->flush($socialActivity);

        return $this->createJSONResponse('', 204);
    }

    /**
     * Deprecated, use `PUT /api/v2/social-activities` instead.
     *
     * @Route("/{id}")
     * @Method("PUT")
     *
     * @ApiDoc(
     *     section="Social Activity",
     *     deprecated=true
     * )
     */
    public function putAction(SocialActivity $socialActivity)
    {
        if ($this->getUser() !== $socialActivity->getRecipient()) {
            throw $this->createNotFoundException();
        }
        //only ignore
        $data = json_decode($this->getRequest()->getContent(), true);
        if (isset($data['ignore'])) {
            $socialActivity->setIgnore($data['ignore']);
        }
        $this->getDoctrine()->getManager()->flush($socialActivity);

        return $this->createJSONResponse('', 200);
    }
}
