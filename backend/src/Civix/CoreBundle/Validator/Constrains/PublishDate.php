<?php

namespace Civix\CoreBundle\Validator\Constrains;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PublishDate extends Constraint
{
    public $message = '{object} is already published';
    public $property = 'publishedAt';
    public $objectName = '';

    public function getTargets()
    {
        return [self::CLASS_CONSTRAINT];
    }
}