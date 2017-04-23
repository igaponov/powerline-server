<?php

namespace Civix\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation()
 */
class FacebookToken extends Constraint
{
    public $message = 'Facebook token is not correct.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy()
    {
        return 'civix_core.validator.facebook_token';
    }
}
