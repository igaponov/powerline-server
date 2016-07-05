<?php

namespace Civix\CoreBundle\Validator\Constraints;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @Annotation
 */
class NotJoinedToGroupValidator extends ConstraintValidator
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function validate($entity, Constraint $constraint)
    {
        $user = $entity->{$constraint->userGetter}();
        $group = $entity->{$constraint->groupGetter}();

        if ($this->em->getRepository('CivixCoreBundle:UserGroup')->isJoinedUser($group, $user)) {
            $this->context->addViolationAt('user', $constraint->message, [], null);
        }
    }
}
