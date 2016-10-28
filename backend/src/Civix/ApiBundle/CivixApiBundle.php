<?php

namespace Civix\ApiBundle;

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
    }
}
