<?php

namespace Civix\ApiBundle\Security\Firewall;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;

class PhoneAuthenticationListener extends AbstractAuthenticationListener
{
    /**
     * @var PhoneNumberUtil
     */
    private $phoneUtil;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        SessionAuthenticationStrategyInterface $sessionStrategy,
        HttpUtils $httpUtils,
        $providerKey,
        AuthenticationSuccessHandlerInterface $successHandler,
        AuthenticationFailureHandlerInterface $failureHandler,
        array $options = array(),
        LoggerInterface $logger = null,
        EventDispatcherInterface $dispatcher = null,
        PhoneNumberUtil $phoneUtil
    ) {
        parent::__construct(
            $tokenStorage,
            $authenticationManager,
            $sessionStrategy,
            $httpUtils,
            $providerKey,
            $successHandler,
            $failureHandler,
            $options,
            $logger,
            $dispatcher
        );
        $this->phoneUtil = $phoneUtil;
    }

    protected function attemptAuthentication(Request $request)
    {
        if (!$phone = $request->get('phone')) {
            return null;
        }
        try {
            $phoneNumber = $this->phoneUtil->parse($phone, PhoneNumberUtil::UNKNOWN_REGION);
        } catch (NumberParseException $e) {
            throw new AuthenticationException($e->getMessage(), Response::HTTP_UNAUTHORIZED, $e);
        }

        $result = $this->authenticationManager->authenticate(new UsernamePasswordToken($phoneNumber, $request->get('code'), $this->providerKey));
        if (!$result->getCredentials()) {
            return new Response('ok');
        }

        return $result;
    }
}