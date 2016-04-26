<?php
namespace Civix\ApiBundle\Form;

use Symfony\Component\Form\DataTransformerInterface;

class KeyToValueTransformer implements DataTransformerInterface
{
    /**
     * @var array
     */
    private $array;

    public function __construct(array $array)
    {
        $this->array = $array;
    }

    public function transform($value)
    {
        if (isset($this->array[$value])) {
            return $this->array[$value];
        }

        return null;
    }

    public function reverseTransform($value)
    {
        $index = array_search($value, $this->array);

        return $index !== false ? $index : null;
    }
}