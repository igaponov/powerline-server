<?php

namespace Civix\ApiBundle\Controller;

use Civix\Component\Notification\Model\AbstractEndpoint;
use Civix\CoreBundle\Service\Notification;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/endpoints")
 */
class EndpointController extends BaseController
{
    /**
     * @var Notification
     * @DI\Inject("civix_core.notification")
     */
    private $notification;

    /**
     * Deprecated, use `GET /api/v2/endpoints` instead.
     *
     * @Route("/", name="api_endpoints_get")
     * @Method("GET")
     *
     * @ApiDoc(
     *     section="Users",
     *     description="List of user's endpoints",
     *     output={
     *          "class" = "array<Civix\CoreBundle\Entity\Notification\AbstractEndpoint>",
     *          "groups" = {"owner-get", "Default"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\CollectionParser",
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     */
    public function getAction()
    {
        $endpoints = $this->getDoctrine()->getManager()
            ->getRepository(AbstractEndpoint::class)
            ->findBy(['user' => $this->getUser()]);
        $response = new Response($this->jmsSerialization($endpoints, array('owner-get', 'Default')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Deprecated, use `POST /api/v2/endpoints` instead.
     *
     * @Route("/", name="api_endpoints_create")
     * @Method("POST")
     *
     * @ApiDoc(
     *     section="Users",
     *     description="Add an endpoint",
     *     input={
     *          "class" = "Civix\CoreBundle\Entity\Notification\AbstractEndpoint",
     *          "groups" = {"owner-create", "Default"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     },
     *     responseMap={
     *          201={
     *              "class" = "Civix\CoreBundle\Entity\Notification\AbstractEndpoint",
     *              "groups" = {"owner-get", "Default"},
     *              "parsers" = {
     *                  "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *              }
     *          }
     *     },
     *     deprecated=true
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function createAction(Request $request): Response
    {
        /* @var AbstractEndpoint $endpoint */
        $endpoint = $this->jmsDeserialization($request->getContent(), AbstractEndpoint::class,
            array('owner-create'));
        $endpoint->setUser($this->getUser());
        $this->notification->handleEndpoint($endpoint);
        $response = new Response($this->jmsSerialization($endpoint, array('owner-get', 'Default')), 201);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
