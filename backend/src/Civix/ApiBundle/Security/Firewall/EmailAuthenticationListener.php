<?php

namespace Civix\ApiBundle\Security\Firewall;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;

class EmailAuthenticationListener extends AbstractAuthenticationListener
{
    protected function attemptAuthentication(Request $request)
    {
        if (!$username = $request->get('username')) {
            return null;
        }
        $credentials = serialize([
            'phone' => $request->get('phone'),
            'zip' => $request->get('zip'),
            'token' => $request->get('token'),
        ]);
        $token = new UsernamePasswordToken($username, $credentials, $this->providerKey);
        $token->setAttribute('isPreAuthentication', !$request->get('phone') && !$request->get('zip'));

        $result = $this->authenticationManager->authenticate($token);
        if (!$result->getAttribute('isPreAuthentication')) {
            return new Response('ok');
        }

        return $result;
    }
}