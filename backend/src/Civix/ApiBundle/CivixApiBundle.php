<?php

namespace Civix\ApiBundle;

use Civix\ApiBundle\DependencyInjection\Security\Factory\EmailFactory;
use Civix\ApiBundle\DependencyInjection\Security\Factory\PhoneLoginFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Civix\ApiBundle\Security\Factory\BearerTokenFactory;

class CivixApiBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        /** @var SecurityExtension $extension */
        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new BearerTokenFactory());
        $extension->addSecurityListenerFactory(new PhoneLoginFactory());
        $extension->addSecurityListenerFactory(new EmailFactory());
    }
}
