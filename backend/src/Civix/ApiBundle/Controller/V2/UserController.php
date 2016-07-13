<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\CoreBundle\Entity\User;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/user")
 */
class UserController extends FOSRestController
{
    /**
     * Profile of the authenticated user
     *
     * @Route("")
     * @Method("GET")
     *
     * @ApiDoc(
     *     authentication = true,
     *     resource=true,
     *     section="Users",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\User",
     *          "groups" = {"api-profile"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     description="Authenticated user's profile",
     *     statusCodes={
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-profile"})
     *
     * @return User
     */
    public function getAction()
    {
        return $this->getUser();
    }
}
