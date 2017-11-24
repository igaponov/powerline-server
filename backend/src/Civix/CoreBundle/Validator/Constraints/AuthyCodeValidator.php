<?php

namespace Civix\CoreBundle\Validator\Constraints;

use Civix\CoreBundle\Service\Authy;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class AuthyCodeValidator extends ConstraintValidator
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
        if (!$constraint instanceof AuthyCode) {
            throw new UnexpectedTypeException($constraint, AuthyCode::class);
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        $phoneNumber = $accessor->getValue($value, $constraint->phoneAttribute);
        $code = $accessor->getValue($value, $constraint->codeAttribute);
        $result = $this->authy->checkVerification($phoneNumber, $code);
        if (!$result['success']) {
            $this->context->addViolation($result['message']);
        }
    }
}