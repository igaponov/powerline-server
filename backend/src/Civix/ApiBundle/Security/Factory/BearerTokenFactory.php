<?php

namespace Civix\ApiBundle\Security\Factory;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;

class BearerTokenFactory implements SecurityFactoryInterface
{
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = 'api.security.authentication.provider.bearer_token.'.$id;
        $container
            ->setDefinition($providerId, new DefinitionDecorator('api.security.authentication.provider.bearer_token'))
            ->replaceArgument(2, $id)
        ;

        $listenerId = 'api.security.authentication.listener.bearer_token.'.$id;
        $container
            ->setDefinition($listenerId, new DefinitionDecorator('api.security.authentication.listener.bearer_token'))
            ->replaceArgument(2, $id)
        ;

        return array($providerId, $listenerId, $defaultEntryPoint);
    }

    public function getPosition()
    {
        return 'pre_auth';
    }

    public function getKey()
    {
        return 'bearer_token';
    }

    public function addConfiguration(NodeDefinition $node)
    {
    }
}
