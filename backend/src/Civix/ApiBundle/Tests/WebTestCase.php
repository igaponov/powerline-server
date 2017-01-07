<?php
namespace Civix\ApiBundle\Tests;

use Civix\CoreBundle\Entity\User;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Response;

abstract class WebTestCase extends \Liip\FunctionalTestBundle\Test\WebTestCase
{
    protected function onNotSuccessfulTest($e)
    {
        $this->containers = [];
        parent::onNotSuccessfulTest($e);
    }

    public function assertResponseHasErrors(Response $response, $errors, $checkExtraErrors = true)
    {
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Validation Failed', $data['message']);
        foreach ($errors as $child => $error) {
            if (is_int($child)) {
                $index = array_search($error, $data['errors']['errors']);
                $this->assertNotFalse($index, "\"$error\" is not in form errors.\nErrors:\n - ".implode("\n - ", $data['errors']['errors']));
                unset($data['errors']['errors'][$index]);
            } elseif (isset($data['errors']['children'][$child]['errors'])) {
                $index = array_search($error, $data['errors']['children'][$child]['errors']);
                $this->assertNotFalse($index, "\"$error\" is not in form[$child] errors.\nErrors:\n - ".implode("\n - ", $data['errors']['children'][$child]['errors']));
                unset($data['errors']['children'][$child]['errors'][$index]);
            } else {
                $this->fail("form[$child] has no errors (\"$error\")");
            }
        }
        if ($checkExtraErrors) {
            if (!empty($data['errors']['errors'])) {
                $this->fail("Form contains extra errors.\nErrors:\n - ".implode("\n - ", $data['errors']['errors']));
            }
            foreach ($data['errors']['children'] as $child => $array) {
                if (!empty($array['errors'])) {
                    $this->fail("Form[$child] contains extra errors.\nErrors:\n - ".implode("\n - ", $array['errors']));
                }
            }
        }
    }

    /**
     * Get the login token user passing the user object.
     * 
     * This method only will authenticate using the same
     * password than the username.
     *
     * @author Habibillah <habibillah@gmail.com>
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
     * @author Habibillah <habibillah@gmail.com>
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

    /**
     * @author Habibillah <habibillah@gmail.com>
     * @param $serializationObject
     * @param $groups
     * @param string $type
     * @return string
     */
    protected function jmsSerialization($serializationObject, $groups, $type = 'json')
    {
        /** @var $serializer \JMS\Serializer\Serializer */
        $serializer = $this->getContainer()->get('jms_serializer');
        $serializerContext = SerializationContext::create()->setGroups($groups);

        return $serializer->serialize($serializationObject, $type, $serializerContext);
    }

    /**
     * @author Habibillah <habibillah@gmail.com>
     * @param $content
     * @param $class
     * @param $groups
     * @param string $type
     * @return array|mixed|object
     */
    protected function jmsDeserialization($content, $class, $groups, $type = 'json')
    {
        /** @var $serializer \JMS\Serializer\Serializer */
        $serializer = $this->getContainer()->get('jms_serializer');
        $serializerContext = DeserializationContext::create()->setGroups($groups);

        return $serializer->deserialize($content, $class, $type, $serializerContext);
    }
}