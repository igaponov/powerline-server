<?php

namespace Civix\ApiBundle\EventListener;

use FOS\RestBundle\View\ConfigurableViewHandlerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class VersionListener
{
    /**
     * @var ViewHandlerInterface
     */
    private $viewHandler;
    /**
     * @var string
     */
    private $defaultVersion;

    public function __construct(ViewHandlerInterface $viewHandler, $defaultVersion)
    {
        $this->viewHandler = $viewHandler;
        $this->defaultVersion = $defaultVersion;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        
        if ($request->attributes->has('version')) {
            $version = $request->attributes->get('version');
        } else {
            $version = $this->defaultVersion;
        }
        
        $request->attributes->set('version', $version);
        if ($this->viewHandler instanceof ConfigurableViewHandlerInterface) {
            $this->viewHandler->setExclusionStrategyVersion($version);
        }
    }
}
