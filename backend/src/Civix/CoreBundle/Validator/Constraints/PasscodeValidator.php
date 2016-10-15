<?php
namespace Civix\CoreBundle\Validator\Constraints;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Model\Group\Worksheet;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PasscodeValidator extends ConstraintValidator
{
    /**
     * @param Worksheet $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Worksheet) {
            throw new UnexpectedTypeException($value, Worksheet::class);
        }
        if (!$value->getUser() instanceof User) {
            throw new UnexpectedTypeException($value->getUser(), User::class);
        }
        if (!$value->getGroup() instanceof Group) {
            throw new UnexpectedTypeException($value->getGroup(), Group::class);
        }

        if (!$constraint instanceof Passcode) {
            throw new UnexpectedTypeException($constraint, Passcode::class);
        }

        $user = $value->getUser();
        $group = $value->getGroup();

        if (
            $group->getMembershipControl() === Group::GROUP_MEMBERSHIP_PASSCODE
            && !$user->getInvites()->contains($group)
            && $value->getPasscode() !== $group->getMembershipPasscode()
        ) {
            $this->context->addViolationAt('passcode', $constraint->message);
        }
    }
}