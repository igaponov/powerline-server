<?php
namespace Civix\ApiBundle\Tests;

use Civix\CoreBundle\Entity\User;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;

abstract class WebTestCase extends \Liip\FunctionalTestBundle\Test\WebTestCase
{
    /**
     * @param User $user
     * @return string
     */
    protected function getLoginToken($user)
    {
        $client = static::createClient();
        $client->request('POST', '/api/secure/login', [
            "username" => $user->getUsername(),
            "password" => $user->getUsername()
        ]);

        $response = $client->getResponse();

        if ($response->getStatusCode() == 200) {
            return json_decode($response->getContent())->token;
        }

        return "";
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