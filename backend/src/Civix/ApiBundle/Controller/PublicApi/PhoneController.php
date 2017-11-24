<?php

namespace Civix\ApiBundle\Controller\PublicApi;

use Civix\CoreBundle\Service\Authy;
use FOS\RestBundle\Controller\Annotations as REST;
use FOS\RestBundle\Request\ParamFetcher;
use GuzzleHttp\Command\ServiceClientInterface;
use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Route("/phone")
 */
class PhoneController
{
    /**
     * @var ServiceClientInterface
     */
    private $authy;
    /**
     * @var PhoneNumberUtil
     */
    private $phoneUtil;

    /**
     * TwilioController constructor.
     * @param Authy $authy
     * @param PhoneNumberUtil $phoneUtil
     */
    public function __construct(
        Authy $authy,
        PhoneNumberUtil $phoneUtil
    ) {
        $this->authy = $authy;
        $this->phoneUtil = $phoneUtil;
    }

    /**
     * @REST\Post("/verification")
     *
     * @REST\RequestParam(name="phone", allowBlank=false, requirements=@PhoneNumber(), description="Phone in E.164 format.")
     *
     * @ApiDoc(
     *     resource=true,
     *     section="Twilio",
     *     description="Verify a phone number",
     *     statusCodes={
     *         200="ok",
     *         400={
     *              "Phone number is invalid",
     *              "https://docs.authy.com/errors.html"
     *         }
     *     }
     * )
     *
     * @param ParamFetcher $params
     * @return \Symfony\Component\Form\FormInterface|Response
     */
    public function postVerificationAction(ParamFetcher $params)
    {
        $phoneNumber = $this->phoneUtil->parse($params->get('phone'), null);
        $result = $this->authy->startVerification($phoneNumber);
        if (!$result['success']) {
            throw new BadRequestHttpException($result['message']);
        }

        return new Response('ok');
    }
}