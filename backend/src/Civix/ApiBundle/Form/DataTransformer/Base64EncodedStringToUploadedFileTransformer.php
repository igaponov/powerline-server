<?php
namespace Civix\ApiBundle\Form\DataTransformer;

use Civix\ApiBundle\Helper\Base64ToFileConverter;
use Symfony\Component\Form\DataTransformerInterface;

class Base64EncodedStringToUploadedFileTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        return;
    }

    public function reverseTransform($value)
    {
        return Base64ToFileConverter::convert($value);
    }
}