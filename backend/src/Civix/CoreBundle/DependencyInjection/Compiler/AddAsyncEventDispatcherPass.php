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
        $serviceId = (string) $container->getAlias('event_dispatcher');
        if ($serviceId === 'debug.event_dispatcher') {
            $serviceId = 'debug.event_dispatcher.parent';
        }
        $dispatcherDefinition = $container->getDefinition($serviceId);
        $newDispatcherDefinition = new Definition(
            AsyncEventDispatcher::class,
            [$dispatcherDefinition]
        );
        $newDispatcherDefinition->setPublic(false)
            ->setDecoratedService($serviceId);
        $container->setDefinition('async.event_dispatcher', $newDispatcherDefinition);
    }
}