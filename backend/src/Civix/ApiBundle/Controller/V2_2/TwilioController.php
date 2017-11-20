<?php

namespace Civix\ApiBundle\Controller\V2_2;

use Civix\CoreBundle\Service\Twilio;
use FOS\RestBundle\Controller\Annotations as REST;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/twilio")
 */
class TwilioController
{
    /**
     * @var Twilio
     */
    private $twilio;

    public function __construct(Twilio $twilio)
    {
        $this->twilio = $twilio;
    }

    /**
     * [How to Add Live Support Chat](https://www.twilio.com/blog/2017/06/complete-chat-application-javascript.html)
     *
     * **Output Format**
     *
     *      {
     *          "identity": "your_identity",
     *          "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImN0eSI6InR3aWt..."
     *      }
     *
     * @REST\Get("/token")
     *
     * @REST\QueryParam(name="identity", requirements="\w+", description="User representation.", allowBlank=false, strict=true)
     * @REST\QueryParam(name="endpoint_id", requirements="\w+", description="Device/app representation.", allowBlank=false, strict=true)
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Twilio",
     *     description="Get Twilio chat token",
     *     statusCodes={
     *         200="Token"
     *     }
     * )
     *
     * @param ParamFetcher $params
     *
     * @return JsonResponse
     */
    public function getTokenAction(ParamFetcher $params): JsonResponse
    {
        $identity = $params->get('identity');
        $token = $this->twilio->getChatToken($identity, $params->get('endpoint_id'));

        return new JsonResponse([
            'identity' => $identity,
            'token' => $token->toJWT(),
        ]);
    }
}