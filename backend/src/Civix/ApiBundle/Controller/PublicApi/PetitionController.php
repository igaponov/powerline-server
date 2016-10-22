<?php

namespace Civix\ApiBundle\Controller\PublicApi;

use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Civix\ApiBundle\Controller\BaseController;
use Civix\CoreBundle\Entity\Poll\Question\Petition;

/**
 * @Route("/petition")
 */
class PetitionController extends BaseController
{
    /**
     * @Route("/{id}", requirements={"id"="\d+"}, name="api_public_petition_info")
     * @Method("GET")
     * @ParamConverter(
     *      "petition",
     *      class="CivixCoreBundle:Poll\Question\Petition",
     *      options={"repository_method" = "getPublishPetitonById"}
     * )
     * @ApiDoc(
     *     resource=true,
     *     section="Polls",
     *     description="Public",
     *     statusCodes={
     *         200="Returns petition",
     *         405="Method Not Allowed"
     *     }
     * )
     */
    public function getPetitionById(Petition $petition)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        $response->setContent($this->jmsSerialization(
            $petition,
            array('api-poll'))
        );

        return $response;
    }

    /**
     * Deprecated, use `GET /api/v2/polls/{id}/comments` instead
     *
     * @Route("/{id}/comments", requirements={"id"="\d+"}, name="api_public_petition_comments")
     * @Method("GET")
     *
     * @ApiDoc(
     *     section="Polls",
     *     description="Public",
     *     statusCodes={
     *         200="Returns petition's comments",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     */
    public function getPetitionComments(Petition $petition)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $comments = $entityManager
            ->getRepository('CivixCoreBundle:Poll\Comment')
            ->getCommentsByPetition($petition);

        $response = new Response($this->jmsSerialization($comments, array('api-comments', 'api-comments-parent')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
