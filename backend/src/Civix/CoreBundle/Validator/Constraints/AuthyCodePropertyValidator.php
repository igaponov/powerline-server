<?php

namespace Civix\CoreBundle\Validator\Constraints;

use Civix\CoreBundle\Service\Authy;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class AuthyCodePropertyValidator extends ConstraintValidator
{
    /**
     * @var Authy
     */
    private $authy;

    public function __construct(Authy $authy)
    {
        $this->authy = $authy;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof AuthyCodeProperty) {
            throw new UnexpectedTypeException($constraint, AuthyCodeProperty::class);
        }

        $phoneNumber = $constraint->phoneValue;
        $result = $this->authy->checkVerification($phoneNumber, $value);
        if (!$result['success']) {
            $this->context->addViolation($result['message']);
        }
    }
}