<?php
namespace Civix\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Passcode extends Constraint
{
    public $message = 'Incorrect passcode.';

    public function getTargets()
    {
        return [self::CLASS_CONSTRAINT];
    }
}