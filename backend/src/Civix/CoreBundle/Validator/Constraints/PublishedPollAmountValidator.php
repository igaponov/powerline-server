<?php
namespace Civix\CoreBundle\Validator\Constraints;

use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Service\QuestionLimit;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PublishedPollAmountValidator extends ConstraintValidator
{
    /**
     * @var QuestionLimit
     */
    private $limit;

    public function __construct(QuestionLimit $limit)
    {
        $this->limit = $limit;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof PublishedPollAmount) {
            throw new UnexpectedTypeException($constraint, PublishedPollAmount::class);
        }

        if (!$value instanceof Question) {
            throw new UnexpectedTypeException($value, Question::class);
        }

        if (!$this->limit->checkLimits($value->getGroup())) {
            $this->context->addViolation($constraint->message);
        }
    }
}