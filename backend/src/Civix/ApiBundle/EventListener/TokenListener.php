<?php

namespace Civix\ApiBundle\EventListener;

use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;

class TokenListener
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function generateToken(AuthenticationEvent $event)
    {
        $token = $event->getAuthenticationToken();
        if ($token instanceof UsernamePasswordToken && $token->isAuthenticated() && $token->getProviderKey() === 'mobileuser_login') {
            /** @var User $user */
            $user = $token->getUser();
            $user->generateToken();
            $this->em->flush();
        }
    }
}