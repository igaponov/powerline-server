<?php

namespace Civix\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class CivixCoreExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $fileLocator = new FileLocator(__DIR__.'/../Resources/config');
        $loader = new DelegatingLoader(
            new LoaderResolver([
                new Loader\XmlFileLoader($container, $fileLocator),
                new Loader\YamlFileLoader($container, $fileLocator),
            ])
        );
        $loader->load('services.xml');
        $loader->load('services.yml');
        $loader->load('geocoder.yml');
        $loader->load('notification.yml');
        $loader->load('propublica.yml');
        $loader->load('command.yml');
        $loader->load('thumbnail_generator.yml');

        $container->setAlias('mailgun.client', $config['mailgun_client']);
        $container->setAlias('mailgun.public_client', $config['mailgun_public_client']);

        if ($container->getParameter('kernel.environment') === 'test') {
            $loader->load('test.xml');
        } else {
            $loader->load('prod.xml');
        }
    }
}
