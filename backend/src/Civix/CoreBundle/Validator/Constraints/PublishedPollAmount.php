<?php
namespace Civix\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PublishedPollAmount extends Constraint
{
    public $message = 'Published poll amount has been reached';

    public function getTargets()
    {
        return [self::CLASS_CONSTRAINT];
    }

    public function validatedBy()
    {
        return 'civix_core.validator.published_poll_amount';
    }

}