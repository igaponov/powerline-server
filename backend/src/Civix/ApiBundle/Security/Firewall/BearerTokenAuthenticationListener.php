<?php

namespace Civix\ApiBundle\Security\Firewall;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Firewall\AbstractPreAuthenticatedListener;

class BearerTokenAuthenticationListener extends AbstractPreAuthenticatedListener
{
    protected function getPreAuthenticatedData(Request $request)
    {
        $apiToken = null;

        // Plain auth based in HTTP_Token header for userType = user
        if ($request->headers->has('Token')) 
        {
            $credentials = $request->headers->get('Token');
        } 
        // Check auth based in Authorization header and other userType
        elseif ($request->headers->has('Authorization')) 
        {
            $isTokenAuth = preg_match(
                '/^Bearer type="(?P<type>\S+?)"\s+token="(?P<token>\S+?)"$/i',
                $request->headers->get('Authorization'),
                $matches
            );
            if ($isTokenAuth) 
            {
                $credentials = $matches['token'];
            }
        }

        if (isset($credentials))
        {
            return ['user', $credentials];
        } else {
            throw new BadCredentialsException();
        }
    }
}
