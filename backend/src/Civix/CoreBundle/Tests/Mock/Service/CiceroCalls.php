<?php

namespace Civix\CoreBundle\Tests\Mock\Service;

use Civix\CoreBundle\Service\CiceroCalls as BaseCiceroCalls;
use Symfony\Bridge\Monolog\Logger;

class CiceroCalls extends BaseCiceroCalls
{
    private $apiLogin;
    private $apiPassword;
    private $logger;

    public function __construct($login, $password, Logger $logger)
    {
        $this->apiLogin = $login;
        $this->apiPassword = $password;
        $this->logger = $logger;
    }

    public function findRepresentativeByLocation($address, $city, $state, $country = 'US')
    {
        return [];
    }

    public function findRepresentativeByOfficialData($firstName, $lastName, $officialTitle)
    {
        return [];
    }

    public function findRepresentativeByNameAndId($firstName, $lastName, $storageId)
    {
        return [];
    }

    public function findNonLegislativeDistricts()
    {
        return [];
    }

    public function getCreditBalance()
    {
        return false;
    }
}
