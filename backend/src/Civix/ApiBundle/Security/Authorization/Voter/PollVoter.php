<?php
namespace Civix\ApiBundle\Security\Authorization\Voter;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\LeaderContentRootInterface;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\Superuser;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PollVoter implements VoterInterface
{
    const SUBSCRIBE = 'subscribe';
    const ANSWER = 'answer';

    /**
     * Checks if the voter supports the given attribute.
     *
     * @param string $attribute An attribute
     *
     * @return bool    true if this Voter supports the attribute, false otherwise
     */
    public function supportsAttribute($attribute)
    {
        return in_array($attribute, array(
            self::SUBSCRIBE,
            self::ANSWER,
        ));
    }

    /**
     * Checks if the voter supports the given class.
     *
     * @param string $class A class name
     *
     * @return bool    true if this Voter can process the class
     */
    public function supportsClass($class)
    {
        $supportedClass = Question::class;
        return $supportedClass === $class || is_subclass_of($class, $supportedClass);
    }

    /**
     * Returns the vote for the given parameters.
     *
     * This method must return one of the following constants:
     * ACCESS_GRANTED, ACCESS_DENIED, or ACCESS_ABSTAIN.
     *
     * @param TokenInterface $token A TokenInterface instance
     * @param Question $object
     * @param array $attributes An array of attributes associated with the method being invoked
     *
     * @return int Either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        // check if class of this object is supported by this voter
        if (!$this->supportsClass(get_class($object))) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        // check if the voter is used correct, only allow one attribute
        // this isn't a requirement, it's just one easy way for you to
        // design your voter
        if (1 !== count($attributes)) {
            throw new \InvalidArgumentException(
                'Only one attribute is allowed for VIEW, EDIT, or DELETE'
            );
        }

        // set the attribute to check against
        $attribute = $attributes[0];

        /** @var User $user */
        $user = $token->getUser(); // get current logged in user

        // check if the given attribute is covered by this voter
        if (!$this->supportsAttribute($attribute)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        // make sure there is a user object (i.e. that the user is logged in)
        if (!$user instanceof UserInterface) {
            return VoterInterface::ACCESS_DENIED;
        }

        // make sure entity has owner attached to it
        if (!$object->getOwner() instanceof LeaderContentRootInterface) {
            return VoterInterface::ACCESS_DENIED;
        }

        if ($attribute === self::SUBSCRIBE) {
            $root = $object->getRoot();
            if ($root instanceof Group && $root->isMember($user)) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        if ($attribute === self::ANSWER) {
            if ($object instanceof Question\Petition && $object->getIsOutsidersSign()) {
                return VoterInterface::ACCESS_GRANTED;
            }
            $questionOwner = $object->getOwner();
            if ($questionOwner instanceof Superuser) {
                return VoterInterface::ACCESS_GRANTED;
            } elseif ($questionOwner instanceof Group && ($questionOwner->isMember($user) || $questionOwner->isManager($user) || $questionOwner->getOwner()->isEqualTo($user))) {
                return VoterInterface::ACCESS_GRANTED;
            } elseif ($questionOwner instanceof Representative
            && array_search($questionOwner->getDistrict() ? $questionOwner->getDistrict()->getId() : null, $user->getDistrictsIds()) !== false) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        return VoterInterface::ACCESS_DENIED;
    }
}