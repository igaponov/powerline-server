<?php

namespace Civix\CoreBundle\Validator\Constraints;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Service\FacebookApi;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class FacebookTokenValidator extends ConstraintValidator
{
    private $api;

    public function __construct(FacebookApi $facebookApi)
    {
        $this->api = $facebookApi;
    }

    /**
     * @param User $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof User) {
            throw new UnexpectedTypeException($value, User::class);
        }

        if (!$constraint instanceof FacebookToken) {
            throw new UnexpectedTypeException($constraint, FacebookToken::class);
        }

        $token = $value->getFacebookToken();
        $facebookId = $value->getFacebookId();
        if (!$this->api->checkFacebookToken($token, $facebookId)) {
            $this->context->addViolation($constraint->message);
        }
    }
}
