<?php

namespace Civix\ApiBundle\Security\Authentication\Provider;

use Civix\ApiBundle\Security\Core\ApiUserProvider;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Civix\ApiBundle\Security\Authentication\Token\ApiToken;

class ApiProvider implements AuthenticationProviderInterface
{
    /**
     * @var ApiUserProvider
     */
    private $userProvider;

    public function __construct(UserProviderInterface $userProvider)
    {
        $this->userProvider = $userProvider;
    }

    /**
     * @param TokenInterface|ApiToken $token
     * @return ApiToken
     */
    public function authenticate(TokenInterface $token)
    {
        $user = $this->userProvider->loadUserByToken($token);
        
        if ($user) {
            $authenticatedToken = new ApiToken($user->getRoles());
            $authenticatedToken->setUser($user);
            $authenticatedToken->setToken($token->getToken(), $token->getUserType());

            return $authenticatedToken;
        }

        throw new AuthenticationException('Authentication failed.');
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof ApiToken;
    }
}
