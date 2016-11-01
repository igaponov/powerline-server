<?php
namespace Civix\CoreBundle\Validator\Constraints;

use Civix\CoreBundle\Model\Subscription\PackageLimitState;
use Civix\CoreBundle\Service\Subscription\PackageHandler;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PackageStateValidator extends ConstraintValidator
{
    /**
     * @var PackageHandler
     */
    private $packageHandler;

    public function __construct(PackageHandler $packageHandler)
    {
        $this->packageHandler = $packageHandler;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof PackageState) {
            throw new UnexpectedTypeException($constraint, PackageState::class);
        }

        /** @var PackageLimitState $packageState */
        $packageState = call_user_func([$this->packageHandler, $constraint->method], $value);
        if (!$packageState->isAllowed()) {
            $this->context->addViolation($constraint->message);
        }
    }
}