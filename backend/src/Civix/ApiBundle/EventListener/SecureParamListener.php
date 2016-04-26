<?php

namespace Civix\ApiBundle\EventListener;

use Civix\ApiBundle\Configuration\SecureParam;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Listens to SecureParam annotations.
 */
class SecureParamListener implements EventSubscriberInterface
{
    /**
     * @var SecurityContextInterface
     */
    private $securityContext;

    /**
     * @param SecurityContextInterface $securityContext
     */
    public function __construct(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }
    
    /**
     * Checks if current user in security context has enough permissions set for accessing a given entity.
     *
     * @param FilterControllerEvent $event A FilterControllerEvent instance
     * @throws \RuntimeException
     * @throws AccessDeniedException;
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        if (!is_array($controller = $event->getController())) {
            return;
        }

        $request = $event->getRequest();

        /** @var SecureParam[] $configurationList */
        if (!$configurationList = $request->attributes->get('_secureparam')) {
            return;
        }

        // multiple instances of @SecureParam is allowed
        foreach ($configurationList as $configuration) { 
            if (!$request->attributes->has($configuration->getEntity())) {
                throw new \RuntimeException(sprintf('Expected entity "%s" not found in request attributes.', $configuration->getEntity()));
            }

            if (!$configuration->getPermission()) {
                throw new \RuntimeException(sprintf('Required argument not provided: "permission"'));
            }

            $this->checkPermission(
                $request->attributes->get($configuration->getEntity()),
                $configuration->getPermission()
            );
        }
    }

    /**
     * Get subscribed events
     *
     * @return array Subscribed events
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => 'onKernelController',
        );
    }

    /**
     * Check permissions
     *
     * @param object $entity Entity to check permissions for
     * @param string $permission Permission to check
     * @throws AccessDeniedException
     */
    public function checkPermission($entity, $permission)
    {
        if (false === $this->securityContext->isGranted($permission, $entity)) {
            throw new AccessDeniedException('You are not allowed to access this resource..');
        }
    }
}