<?php

namespace Civix\ApiBundle\Controller;

use Civix\CoreBundle\Service\Notification as NotificationService;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Civix\CoreBundle\Entity\Notification;

/**
 * @Route("/endpoints")
 */
class EndpointController extends BaseController
{
    /**
     * @var NotificationService
     * @DI\Inject("civix_core.notification")
     */
    private $notification;

    /**
     * @Route("/", name="api_endpoints_get")
     * @Method("GET")
     *
     * @ApiDoc(
     *     resource=true,
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
     *     }
     * )
     */
    public function getAction()
    {
        $endpoints = $this->getDoctrine()->getManager()
            ->getRepository(Notification\AbstractEndpoint::class)
            ->findBy(['user' => $this->getUser()]);
        $response = new Response($this->jmsSerialization($endpoints, array('owner-get', 'Default')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
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
     *     }
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function createAction(Request $request)
    {
        /* @var Notification\AbstractEndpoint $endpoint */
        $endpoint = $this->jmsDeserialization($request->getContent(), Notification\AbstractEndpoint::class,
            array('owner-create'));
        $endpoint->setUser($this->getUser());
        $this->notification->handleEndpoint($endpoint);
        $response = new Response($this->jmsSerialization($endpoint, array('owner-get', 'Default')), 201);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
