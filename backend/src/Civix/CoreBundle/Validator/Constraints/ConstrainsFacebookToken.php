<?php

namespace Civix\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ConstrainsFacebookToken extends Constraint
{
    public $message = 'Facebook token is not correct';

    public function validatedBy()
    {
        return 'civix_core.validator.facebook_token';
    }
}
