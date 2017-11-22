<?php

namespace Civix\ApiBundle\Security\Authentication\Provider;

use Civix\ApiBundle\Security\Core\UserProviderInterface;
use Civix\CoreBundle\Entity\RecoveryToken;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Event\RecoveryTokenEvent;
use Civix\CoreBundle\Event\RecoveryTokenEvents;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Provider\UserAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class EmailAuthenticationProvider extends UserAuthenticationProvider
{
    /**
     * @var UserProviderInterface
     */
    private $userProvider;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var PhoneNumberUtil
     */
    private $phoneUtil;

    public function __construct(
        UserProviderInterface $userProvider,
        UserCheckerInterface $userChecker,
        $providerKey,
        EntityManagerInterface $em,
        EventDispatcherInterface $dispatcher,
        PhoneNumberUtil $phoneUtil,
        $hideUserNotFoundExceptions = true
    ) {
        parent::__construct($userChecker, $providerKey, $hideUserNotFoundExceptions);
        $this->userProvider = $userProvider;
        $this->em = $em;
        $this->dispatcher = $dispatcher;
        $this->phoneUtil = $phoneUtil;
    }

    /**
     * @param string $username
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
            $user = $this->userProvider->loadUserByUsername($user);

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

    /**
     * @param UserInterface|User $user
     * @param UsernamePasswordToken $token
     * @throws \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        $credentials = unserialize($token->getCredentials());
        if (!$credentials || empty($credentials['token'])) {
            throw new AuthenticationException('Invalid credentials.', Response::HTTP_UNAUTHORIZED);
        }
        if (empty($credentials['phone']) && empty($credentials['zip'])) {
            $recoveryToken = $this->em->getRepository(RecoveryToken::class)
                ->findOneBy(['deviceToken' => sha1($user->getUsername().$credentials['token'])]);
            if (!$recoveryToken) {
                throw new AuthenticationException('Recovery token is not found.');
            }
            if (!$recoveryToken->isConfirmed()) {
                throw new AuthenticationException('Recovery token is not confirmed.');
            }
            return;
        }
        $phone = $user->getPhone();
        try {
            $phoneNumber = $phone ? $this->phoneUtil->parse($credentials['phone'], null) : null;
        } catch (NumberParseException $e) {
            throw new AuthenticationException('Invalid phone number.', 0, $e);
        }
        $isPhoneEmpty = !$phone && !$phoneNumber;
        if (($isPhoneEmpty || $phone->equals($phoneNumber)) && $user->getZip() === $credentials['zip']) {
            $recoveryToken = new RecoveryToken($user, $credentials['token']);
            $this->em->persist($recoveryToken);
            $this->em->flush();

            $event = new RecoveryTokenEvent($recoveryToken);
            $this->dispatcher->dispatch(RecoveryTokenEvents::CREATE, $event);
            return;
        }
        throw new AuthenticationException('Invalid credentials.');
    }
}