<?php

namespace Civix\CoreBundle\DependencyInjection\Compiler;

use Civix\CoreBundle\Service\AsyncEventDispatcher;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class AddAsyncEventDispatcherPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $serviceId = 'event_dispatcher';
        $dispatcherDefinition = $container->findDefinition($serviceId);
        $newDispatcherDefinition = new Definition(
            AsyncEventDispatcher::class,
            [$dispatcherDefinition]
        );
        $newDispatcherDefinition->setDecoratedService($serviceId);
        $container->setDefinition('async.event_dispatcher', $newDispatcherDefinition);
    }
}