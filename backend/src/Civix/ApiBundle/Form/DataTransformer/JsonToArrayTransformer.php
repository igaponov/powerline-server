<?php
namespace Civix\ApiBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class JsonToArrayTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        return json_encode((array)$value);
    }

    public function reverseTransform($value)
    {
        if (is_string($value)) {
            return (array)json_decode($value, true);
        }

        return $value;
    }
}