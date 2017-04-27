<?php

namespace Civix\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SetUploaderFileLocatorDirsPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
//        $decoratedDefinition = $container->getDefinition('vich_uploader.metadata.file_locator');
//        $definition = $container->getDefinition('civix_core.uploader.metadata.file_locator');
//        $definition->replaceArgument(0, $decoratedDefinition->getArgument(0));
    }
}