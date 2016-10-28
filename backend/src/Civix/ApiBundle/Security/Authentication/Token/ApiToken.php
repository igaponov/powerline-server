<?php

namespace Civix\ApiBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class ApiToken extends AbstractToken
{
    /**
     * @var string
     */
    private $token;

    public function __construct($roles = [])
    {
        parent::__construct($roles);
        parent::setAuthenticated(count($this->getRoles()) > 0);
    }

    public function getCredentials()
    {
        return '';
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function getToken()
    {
        return $this->token;
    }
}
