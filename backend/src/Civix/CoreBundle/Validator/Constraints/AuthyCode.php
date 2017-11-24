<?php

namespace Civix\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation()
 */
class AuthyCode extends Constraint
{
    public $phoneAttribute;
    public $codeAttribute;

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return 'civix_core.validator.authy_code';
    }

    public function getRequiredOptions(): array
    {
        return ['phoneAttribute', 'codeAttribute'];
    }
}