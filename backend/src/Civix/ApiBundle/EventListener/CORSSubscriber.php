<?php

namespace Civix\ApiBundle\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Response;

class CORSSubscriber implements EventSubscriberInterface
{
    private $headers = [
        'content-disposition',
        'accept',
        'origin',
        'x-requested-with',
        'token',
        'content-type',
    ];

    private $methods = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'PATCH',
    ];

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array(array('onKernelRequest', 33)),
        );
    }

    public function onKernelRequest(GetResponseEvent $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $request = $event->getRequest();

        // skip if not a CORS request
        if (!$request->headers->has('Origin') || $request->headers->get('Origin') == $request->getSchemeAndHttpHost()) {
            return;
        }

        if ('OPTIONS' === $request->getMethod()) {
            $event->setResponse($this->getPreflightResponse());
            return;
        }

        $dispatcher->addListener('kernel.response', array($this, 'onKernelResponse'));
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', implode(', ', $this->headers));
    }

    protected function getPreflightResponse()
    {
        $response = new Response();

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', implode(', ', $this->headers));
        $response->headers->set('Access-Control-Allow-Methods', implode(', ', $this->methods));

        return $response;
    }
}
