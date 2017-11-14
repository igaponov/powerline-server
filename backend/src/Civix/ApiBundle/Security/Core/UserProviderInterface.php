<?php

namespace Civix\ApiBundle\Security\Core;

use Civix\CoreBundle\Entity\User;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use libphonenumber\PhoneNumber;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface as BaseUserProviderInterface;

interface UserProviderInterface extends BaseUserProviderInterface, OAuthAwareUserProviderInterface
{
    /**
     * @param TokenInterface $token
     * @return User|null
     */
    public function loadUserByToken(TokenInterface $token);

    /**
     * @param PhoneNumber $phone
     * @return User|null
     */
    public function loadUserByPhone(PhoneNumber $phone);

}