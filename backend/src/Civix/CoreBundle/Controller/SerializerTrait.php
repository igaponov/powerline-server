<?php

namespace Civix\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\DeserializationContext;

trait SerializerTrait
{
    protected function createJSONResponse($content = '', $status = 200)
    {
        return new JsonResponse($content, $status);;
    }

    protected function jmsSerialization($serializationObject, $groups, $type = 'json')
    {
        /** @var $serializer \JMS\Serializer\Serializer */
        $serializer = $this->get('jms_serializer');
        $serializerContext = SerializationContext::create()->enableMaxDepthChecks()->setGroups($groups);

        return $serializer->serialize($serializationObject, $type, $serializerContext);
    }

    protected function jmsDeserialization($content, $class, $groups, $type = 'json')
    {
        /** @var $serializer \JMS\Serializer\Serializer */
        $serializer = $this->get('jms_serializer');
        $serializerContext = DeserializationContext::create()->enableMaxDepthChecks()->setGroups($groups);

        return $serializer->deserialize($content, $class, $type, $serializerContext);
    }
}
