<?php

namespace Civix\CoreBundle\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PublishDateValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof PublishDate) {
            throw new UnexpectedTypeException($constraint, PublishDate::class);
        }

        if (!is_object($value)) {
            throw new UnexpectedTypeException($value, 'object');
        }

        $propertyAccessor = new PropertyAccessor();
        $date = $propertyAccessor->getValue($value, $constraint->property);

        if ($date && $date < new \DateTime()) {
            $this->context->addViolation(
                $constraint->message,
                ['{object}' => $constraint->objectName ? : substr(strrchr(get_class($value), "\\"), 1)]
            );
        }
    }
}