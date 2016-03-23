<?php
namespace Civix\ApiBundle\Tests;

use Civix\CoreBundle\Entity\User;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;

abstract class WebTestCase extends \Liip\FunctionalTestBundle\Test\WebTestCase
{
    /**
     * Get the login token user passing the user object.
     * 
     * This method only will authenticate using the same
     * password than the username.
     * 
     * @param User $user
     * @return NULL|string
     */
    protected function getLoginToken($user)
    {
    	return $this->getUserToken($user->getUsername(), $user->getUsername());
    }
    
    /**
     * Get the login token user passing username and password credentials
     * 
     * @param string $username
     * @param string $password
     * 
     * @return NULL|string
     */
    protected function getUserToken($username = NULL, $password = NULL)
    {
    	$client = static::createClient();
    	$client->request('POST', '/api/secure/login', [
    			'username' => $username,
    			'password' => $password
    	]);
    
    	$response = $client->getResponse();
    
    	if ($response->getStatusCode() == 200) 
    	{
    		return json_decode($response->getContent())->token;
    	}
    
    	return NULL;
    }

    protected function jmsSerialization($serializationObject, $groups, $type = 'json')
    {
        /** @var $serializer \JMS\Serializer\Serializer */
        $serializer = $this->getContainer()->get('jms_serializer');
        $serializerContext = SerializationContext::create()->setGroups($groups);

        return $serializer->serialize($serializationObject, $type, $serializerContext);
    }

    protected function jmsDeserialization($content, $class, $groups, $type = 'json')
    {
        /** @var $serializer \JMS\Serializer\Serializer */
        $serializer = $this->getContainer()->get('jms_serializer');
        $serializerContext = DeserializationContext::create()->setGroups($groups);

        return $serializer->deserialize($content, $class, $type, $serializerContext);
    }
}