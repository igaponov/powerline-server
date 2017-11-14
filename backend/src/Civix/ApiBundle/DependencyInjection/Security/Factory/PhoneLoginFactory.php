<?php

namespace Civix\ApiBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class PhoneLoginFactory extends AbstractFactory
{
    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId): string
    {
        $provider = 'civix_api.security.authentication.provider.phone.'.$id;
        $container
            ->setDefinition($provider, new DefinitionDecorator('civix_api.security.authentication.provider.phone'))
            ->replaceArgument(1, new Reference('security.user_checker.'.$id))
            ->replaceArgument(2, $id)
        ;

        return $provider;
    }

    protected function getListenerId(): string
    {
        return 'civix_api.security.authentication.listener.phone';
    }

    public function getPosition(): string
    {
        return 'form';
    }

    public function getKey(): string
    {
        return 'phone-login';
    }

    protected function createListener($container, $id, $config, $userProvider)
    {
        $listenerId = $this->getListenerId();
        $listener = new DefinitionDecorator($listenerId);
        $listener->replaceArgument(4, $id);
        $listener->replaceArgument(5, new Reference($this->createAuthenticationSuccessHandler($container, $id, $config)));
        $listener->replaceArgument(6, new Reference($this->createAuthenticationFailureHandler($container, $id, $config)));
        $listener->replaceArgument(7, array_intersect_key($config, $this->options));
        $listener->addArgument(new Reference('libphonenumber.phone_number_util'));

        $listenerId .= '.'.$id;
        $container->setDefinition($listenerId, $listener);

        return $listenerId;
    }
}