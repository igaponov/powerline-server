<?php

namespace Civix\CoreBundle\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PropertyValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Property) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\Property');
        }

        if (null === $value) {
            return;
        }

        $context = $this->context;

        $validator = $context->getValidator()->inContext($context);
        $accessor = PropertyAccess::createPropertyAccessor();
        $value = $accessor->getValue($value, $constraint->propertyPath);

        $validator->validate($value, $constraint->constraints, $constraint->groups);
    }
}