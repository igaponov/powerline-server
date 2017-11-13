<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Form\Type\EndpointType;
use Civix\Component\Notification\Model\AbstractEndpoint;
use Civix\CoreBundle\Service\Notification;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/endpoints")
 */
class EndpointController extends FOSRestController
{
    /**
     * @var Notification
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
     * @return AbstractEndpoint[]
     */
    public function getAction(): array
    {
        $endpoints = $this->em
            ->getRepository(AbstractEndpoint::class)
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
     * @return AbstractEndpoint|Form
     */
    public function postEndpointAction(Request $request)
    {
        $form = $this->createForm(EndpointType::class);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            /** @var AbstractEndpoint $endpoint */
            $endpoint = $form->getData();
            $endpoint->setUser($this->getUser());

            return $this->notification->handleEndpoint($endpoint);
        }

        return $form;
    }
}
