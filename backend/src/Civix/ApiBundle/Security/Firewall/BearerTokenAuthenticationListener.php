<?php

namespace Civix\ApiBundle\Security\Firewall;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Firewall\AbstractPreAuthenticatedListener;

class BearerTokenAuthenticationListener extends AbstractPreAuthenticatedListener
{
    const BEARER_TOKEN = '/^Bearer (?P<token>\S+?)$/i';

    const BEARER_TOKEN_TYPE = '/^Bearer type="(?P<type>\S+?)"\s+token="(?P<token>\S+?)"$/i';

    protected function getPreAuthenticatedData(Request $request)
    {
        $apiToken = null;

        // Plain auth based in HTTP_Token header for userType = user
        if ($request->headers->has('Token')) {
            $credentials = $request->headers->get('Token');
        } elseif ($header = $request->headers->get('Authorization')) {
            // Check auth based in Authorization header and other userType
            if (preg_match(self::BEARER_TOKEN, $header, $matches)) {
                $credentials = $matches['token'];
            } elseif (preg_match(self::BEARER_TOKEN_TYPE, $header, $matches)) {
                $credentials = $matches['token'];
            }
        }

        if (isset($credentials)) {
            return ['user', $credentials];
        } else {
            throw new BadCredentialsException();
        }
    }
}
