<?php

namespace Civix\CoreBundle\Service;

use GuzzleHttp\Command\Result;
use GuzzleHttp\Command\ServiceClientInterface;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;

class Authy
{
    /**
     * @var ServiceClientInterface
     */
    private $client;
    /**
     * @var PhoneNumberUtil
     */
    private $phoneUtil;

    public function __construct(
        ServiceClientInterface $client,
        PhoneNumberUtil $phoneUtil = null
    ) {
        $this->client = $client;
        $this->phoneUtil = $phoneUtil ?: PhoneNumberUtil::getInstance();
    }

    public function startVerification(PhoneNumber $phoneNumber): Result
    {
        $type = $this->phoneUtil->getNumberType($phoneNumber);

        return \call_user_func([$this->client, 'startVerification'], [
            'country_code' => $phoneNumber->getCountryCode(),
            'phone_number' => $phoneNumber->getNationalNumber(),
            'via' => $type === PhoneNumberType::MOBILE ? 'sms' : 'call',
        ]);
    }

    public function checkVerification(PhoneNumber $phoneNumber, string $code): Result
    {
        return \call_user_func([$this->client, 'checkVerification'], [
            'country_code' => $phoneNumber->getCountryCode(),
            'phone_number' => $phoneNumber->getNationalNumber(),
            'verification_code' => $code,
        ]);
    }
}