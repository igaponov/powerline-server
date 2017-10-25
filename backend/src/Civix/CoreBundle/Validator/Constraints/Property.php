<?php

namespace Civix\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Property extends Constraint
{
    public $message = '';
    public $constraints = array();
    public $propertyPath;

    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}