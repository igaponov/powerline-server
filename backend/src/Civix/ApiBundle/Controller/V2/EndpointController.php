<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Form\Type\EndpointType;
use Civix\CoreBundle\Service\Notification as NotificationService;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Civix\CoreBundle\Entity\Notification;

/**
 * @Route("/endpoints")
 */
class EndpointController extends FOSRestController
{
    /**
     * @var NotificationService
     * @DI\Inject("civix_core.notification")
     */
    private $notification;

    /**
     * @var EntityManager
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

    /**
     * List the authenticated user's endpoints
     *
     * @Get("")
     *
     * @ApiDoc(
     *     resource=true,
     *     authentication=true,
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
     *
     * @View(serializerGroups={"owner-get", "Default"})
     *
     * @return Notification\AbstractEndpoint[]
     */
    public function getAction()
    {
        $endpoints = $this->em
            ->getRepository(Notification\AbstractEndpoint::class)
            ->findBy(['user' => $this->getUser()]);

        return $endpoints;
    }

    /**
     * @Post("")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Users",
     *     description="Create an endpoint",
     *     input="Civix\ApiBundle\Form\Type\EndpointType",
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
     * @View(serializerGroups={"owner-get", "Default"}, statusCode=201)
     *
     * @param Request $request
     *
     * @return Notification\AbstractEndpoint|Form
     */
    public function postEndpointAction(Request $request)
    {
        $form = $this->createForm(EndpointType::class);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            /** @var Notification\AbstractEndpoint $endpoint */
            $endpoint = $form->getData();
            $endpoint->setUser($this->getUser());

            return $this->notification->handleEndpoint($endpoint);
        }

        return $form;
    }
}
