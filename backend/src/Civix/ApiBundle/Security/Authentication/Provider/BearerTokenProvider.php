<?php

namespace Civix\ApiBundle\Security\Authentication\Provider;

use Civix\ApiBundle\Security\Core\ApiUserProvider;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class BearerTokenProvider implements AuthenticationProviderInterface
{
    /**
     * @var ApiUserProvider
     */
    private $userProvider;
    /**
     * @var UserCheckerInterface
     */
    private $userChecker;
    /**
     * @var
     */
    private $providerKey;

    public function __construct(UserProviderInterface $userProvider, UserCheckerInterface $userChecker, $providerKey)
    {
        $this->userProvider = $userProvider;
        $this->userChecker = $userChecker;
        $this->providerKey = $providerKey;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return null;
        }

        $user = $this->userProvider->loadUserByToken($token);

        $this->userChecker->checkPostAuth($user);

        $authenticatedToken = new PreAuthenticatedToken($user, $token->getCredentials(), $this->providerKey, $user->getRoles());
        $authenticatedToken->setAttributes($token->getAttributes());

        return $authenticatedToken;
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof PreAuthenticatedToken;
    }
}
