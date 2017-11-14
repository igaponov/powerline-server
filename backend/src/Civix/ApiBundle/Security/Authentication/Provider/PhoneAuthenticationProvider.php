<?php

namespace Civix\ApiBundle\Security\Authentication\Provider;

use Civix\ApiBundle\Security\Core\UserProviderInterface;
use GuzzleHttp\Command\Exception\CommandException;
use GuzzleHttp\Command\ServiceClientInterface;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Provider\UserAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class PhoneAuthenticationProvider extends UserAuthenticationProvider
{
    /**
     * @var UserProviderInterface
     */
    private $userProvider;
    /**
     * @var ServiceClientInterface
     */
    private $client;
    /**
     * @var PhoneNumberUtil
     */
    private $phoneUtil;

    public function __construct(
        UserProviderInterface $userProvider,
        UserCheckerInterface $userChecker,
        $providerKey,
        ServiceClientInterface $client,
        PhoneNumberUtil $phoneUtil,
        $hideUserNotFoundExceptions = true
    ) {
        parent::__construct($userChecker, $providerKey, $hideUserNotFoundExceptions);
        $this->userProvider = $userProvider;
        $this->client = $client;
        $this->phoneUtil = $phoneUtil;
    }

    public function supports(TokenInterface $token)
    {
        return parent::supports($token) && $token->getUser() instanceof PhoneNumber;
    }

    /**
     * @param PhoneNumber $username
     * @param UsernamePasswordToken $token
     * @return \Civix\CoreBundle\Entity\User|mixed|null|UserInterface
     */
    protected function retrieveUser($username, UsernamePasswordToken $token)
    {
        $user = $token->getUser();
        if ($user instanceof UserInterface) {
            return $user;
        }

        try {
            $user = $this->userProvider->loadUserByPhone($user);

            if (!$user instanceof UserInterface) {
                throw new AuthenticationServiceException('The user provider must return a UserInterface object.');
            }

            return $user;
        } catch (UsernameNotFoundException $e) {
            $e->setUsername($username);
            throw $e;
        } catch (\Exception $e) {
            $e = new AuthenticationServiceException($e->getMessage(), 0, $e);
            $e->setToken($token);
            throw $e;
        }
    }

    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        /** @var PhoneNumber $phoneNumber */
        $phoneNumber = $token->getUser();
        $params = [
            'country_code' => $phoneNumber->getCountryCode(),
            'phone_number' => $phoneNumber->getNationalNumber(),
        ];
        if ($code = $token->getCredentials()) {
            $method = 'checkVerification';
            $params['verification_code'] = $code;
        } else {
            $method = 'startVerification';
            $type = $this->phoneUtil->getNumberType($phoneNumber);
            $params['via'] = $type === PhoneNumberType::MOBILE ? 'sms' : 'call';
        }
        try {
            $result = $this->client->$method($params);
        } catch (CommandException $e) {
            throw new AuthenticationException($e->getMessage(), Response::HTTP_UNAUTHORIZED, $e);
        }
        if (!$result['success']) {
            throw new AuthenticationException($result['message']);
        }
    }
}