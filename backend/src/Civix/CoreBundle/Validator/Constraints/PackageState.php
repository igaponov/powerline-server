<?php
namespace Civix\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PackageState extends Constraint
{
    public $message;
    /**
     * @var string Package Handler method name
     */
    public $method;

    public function getTargets()
    {
        return [self::PROPERTY_CONSTRAINT, self::CLASS_CONSTRAINT];
    }

    public function validatedBy()
    {
        return 'civix_core.validator.package_state_validator';
    }
}