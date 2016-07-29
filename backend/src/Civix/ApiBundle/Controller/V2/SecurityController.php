<?php
namespace Civix\ApiBundle\Controller\V2;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use FOS\RestBundle\Controller\FOSRestController as Controller;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/security")
 */
class SecurityController extends Controller
{
    /**
     * Checks user credentials from facebook,
     * creates user if it's not in db, issues authentication token on success
     *
     * @Route("/facebook")
     * @Method({"GET"})
     *
     * @QueryParam(name="code", nullable=false, allowBlank=false, strict=true)
     *
     * @ApiDoc(
     *     resource=true,
     *     section="Security",
     *     description="Checks user credentials from facebook",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\User",
     *          "groups" = {"api-session"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         405="Method Not Allowed"
     *     }
     * )
     */
    public function facebookAction() {
        throw new \RuntimeException("You must configure the check path to be handled by the firewall using oauth in your security firewall configuration.");
    }
}