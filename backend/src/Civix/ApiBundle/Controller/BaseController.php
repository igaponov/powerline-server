<?php

namespace Civix\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\DeserializationContext;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BaseController extends Controller
{
    protected function createJSONResponse($content = '', $status = 200)
    {
        $response = new Response($content, $status);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @return ValidatorInterface
     */
    protected function getValidator()
    {
        return $this->get('validator');
    }

    protected function transformErrors(ConstraintViolationListInterface $errors)
    {
        $result = array();
        foreach ($errors as $error) {
            /* @var $error \Symfony\Component\Validator\ConstraintViolation */
            $result[] = array(
                'property' => $error->getPropertyPath(),
                'message' => $error->getMessage(),
            );
        }

        return $result;
    }

    protected function validate($data, $constraints = null, $groups = null)
    {
        $errors = $this->getValidator()->validate($data, $constraints, $groups);
        if (count($errors) > 0) {
            throw new BadRequestHttpException(json_encode(array('errors' => $this->transformErrors($errors))));
        }
    }

    protected function jmsSerialization($serializationObject, $groups, $type = 'json')
    {
        /** @var $serializer \JMS\Serializer\Serializer */
        $serializer = $this->get('jms_serializer');
        $serializerContext = SerializationContext::create()
            ->setGroups($groups)
            ->setVersion(1);

        return $serializer->serialize($serializationObject, $type, $serializerContext);
    }

    protected function jmsDeserialization($content, $class, $groups, $type = 'json')
    {
        /** @var $serializer \JMS\Serializer\Serializer */
        $serializer = $this->get('jms_serializer');
        $serializerContext = DeserializationContext::create()
            ->setGroups($groups)
            ->setVersion(1);

        return $serializer->deserialize($content, $class, $type, $serializerContext);
    }

    protected function getJson()
    {
        return json_decode($this->getRequest()->getContent());
    }
}
