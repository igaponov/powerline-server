<?php

namespace Civix\CoreBundle;

use Civix\CoreBundle\DependencyInjection\Compiler\AddAsyncEventDispatcherPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CivixCoreBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddAsyncEventDispatcherPass());
    }
}
