<?php

namespace Civix\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation()
 */
class AuthyCodeProperty extends Constraint
{
    public $phoneValue;

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return 'civix_core.validator.authy_code_property';
    }

    public function getRequiredOptions(): array
    {
        return ['phoneValue'];
    }
}