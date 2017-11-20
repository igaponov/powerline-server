<?php

namespace Civix\CoreBundle\Service;

use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\ChatGrant;

class Twilio
{
    /**
     * @var string
     */
    private $serviceSid;
    /**
     * @var string
     */
    private $accountSid;
    /**
     * @var string
     */
    private $signingKeySid;
    /**
     * @var string
     */
    private $secret;

    public function __construct(
        string $serviceSid,
        string $accountSid,
        string $signingKeySid,
        string $secret
    ) {
        $this->serviceSid = $serviceSid;
        $this->accountSid = $accountSid;
        $this->signingKeySid = $signingKeySid;
        $this->secret = $secret;
    }

    public function getChatToken(string $identity, string $endpointId): AccessToken
    {
        $grant = (new ChatGrant())
            ->setServiceSid($this->serviceSid)
            ->setEndpointId($endpointId);
        $token = (new AccessToken($this->accountSid, $this->signingKeySid, $this->secret))
            ->addGrant($grant)
            ->setIdentity($identity);

        return $token;
    }
}