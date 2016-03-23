<?php

namespace Civix\ApiBundle\Security\Firewall;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Civix\ApiBundle\Security\Authentication\Token\ApiToken;

class HeaderAuthenticationListener implements ListenerInterface
{
    protected $securityContext;
    protected $authenticationManager;

    public function __construct(
        SecurityContextInterface $securityContext,
        AuthenticationManagerInterface $authenticationManager
    ) {
        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
    }

    /**
     * Try to autenticate a ApiToken object.
     * 
     * If success it sets the token in the security context and
     * will return TRUE.
     * 
     * If fails, it will return FALSE.
     * 
     * @param ApiToken $apiToken
     * 
     * @return boolean
     */
    private function checkAuth(ApiToken $apiToken = NULL)
    {
    	try
    	{
	    	$authToken = $this->authenticationManager->authenticate($apiToken);
	    	
	    	$this->securityContext->setToken($authToken);
	    	
	    	return TRUE;
    	}
    	catch (AuthenticationException $failed)
    	{
    		return FALSE;
    	}
    }
    
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $apiToken = null;

        // Plain auth based in HTTP_Token header for userType = user
        if ($request->headers->has('Token')) 
        {
            $apiToken = new ApiToken();
            $apiToken->setToken($request->headers->get('Token'), 'user');
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
                $apiToken = new ApiToken();
                $apiToken->setToken($matches['token'], $matches['type']);
            }
        }

        // Check if the token was provided in some point
        if (!$apiToken) 
        {
            $response = new Response();
            $response->setStatusCode(401, 'Authentication required.');
            $event->setResponse($response);

            return;
        }

        // First try to authenticate with userType = user
        if(!$this->checkAuth($apiToken))
        {
        	// The userType = user has failed, we check userType = group
        	$apiToken->setToken($request->headers->get('Token'), 'group');
        	
        	// If group fails, we give up (@todo pending to implement representative and superuser if needed)
        	if(!$this->checkAuth($apiToken))
        	{
        		$response = new Response();
        		$response->setStatusCode(401, 'Incorrect Token or userType.');
        		$event->setResponse($response);
        		
        		return;
        	}
        }
    }
}
