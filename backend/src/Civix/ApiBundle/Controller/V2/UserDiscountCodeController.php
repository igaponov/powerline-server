<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\CoreBundle\Entity\DiscountCode;
use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/user/discount-code")
 */
class UserDiscountCodeController extends FOSRestController
{
    /**
     * Get a discount code of a logged in user
     *
     * @Route("")
     * @Method("GET")
     *
     * @ParamConverter("discountCode", options={"mapping" = {"loggedInUser" = "owner"}}, converter="doctrine.param_converter")
     *
     * @ApiDoc(
     *     authentication = true,
     *     resource=true,
     *     section="Users",
     *     description="Get the authenticated user's discount code",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\DiscountCode",
     *          "groups" = {"Default"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         404="Discount Code Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param DiscountCode $discountCode
     *
     * @return DiscountCode
     */
    public function getDiscountCodesAction(DiscountCode $discountCode)
    {
        return $discountCode;
    }
}