<?php

namespace Civix\ApiBundle\Security\Http\EntryPoint;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class BearerTokenAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new JsonResponse(
            ['message' => 'Authentication failed.'],
            JsonResponse::HTTP_UNAUTHORIZED,
            ['WWW-Authenticate' => 'Bearer']
        );
    }
}