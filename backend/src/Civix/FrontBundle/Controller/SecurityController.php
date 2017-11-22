<?php

namespace Civix\FrontBundle\Controller;

use Civix\CoreBundle\Entity\RecoveryToken;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/security")
 */
class SecurityController
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/recovery/{token}", name="civix_front_security_recovery", requirements={"token"="[\w\+\=\/]+"})
     *
     * @ParamConverter("recoveryToken", class="Civix\CoreBundle\Entity\RecoveryToken", options={
     *     "mapping" = {"token" = "token"},
     *     "repository_method" = "findOneActiveByToken",
     *     "map_method_signature" = true
     * })
     *
     * @param RecoveryToken $recoveryToken
     * @return Response
     */
    public function recoveryAction(RecoveryToken $recoveryToken): Response
    {
        $recoveryToken->confirm();
        $this->em->flush();

        return new Response('E-mail confirmed. Please return to the Powerline app.');
    }
}