<?php
namespace Civix\ApiBundle\Security\Authorization\Voter;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Entity\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PostVoter implements VoterInterface
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';
    const SUBSCRIBE = 'subscribe';
    /**
     * @var GroupVoter
     */
    private $groupVoter;

    public function __construct(GroupVoter $groupVoter)
    {
        $this->groupVoter = $groupVoter;
    }

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
            self::VIEW,
            self::EDIT,
            self::DELETE,
            self::SUBSCRIBE,
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
        $supportedClass = Post::class;
        return $supportedClass === $class || is_subclass_of($class, $supportedClass);
    }

    /**
     * Returns the vote for the given parameters.
     *
     * This method must return one of the following constants:
     * ACCESS_GRANTED, ACCESS_DENIED, or ACCESS_ABSTAIN.
     *
     * @param TokenInterface $token A TokenInterface instance
     * @param Post $object
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
        if (!$object->getUser() instanceof UserInterface) {
            return VoterInterface::ACCESS_DENIED;
        }

        // unsubscribed users from the group can subscribe
        if ($attribute === self::SUBSCRIBE) {
            $group = $object->getGroup();
            $func = function($i, UserGroup $userGroup) use($group) {
                return $userGroup->getStatus() == UserGroup::STATUS_ACTIVE
                    && $group->getId() == $userGroup->getGroup()->getId();
            };
            if ($user->getUserGroups()->exists($func)) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        // group members can view the post
        if ($attribute === self::VIEW) {
            $group = $object->getGroup();
            if ($group instanceof Group) {
                return $this->groupVoter->vote($token, $group, [GroupVoter::MEMBER]);
            }
        }

        // post creator can do anything except subscribing
        if ($attribute !== self::SUBSCRIBE) {
            if ($object->getUser()->isEqualTo($user)) {
                return VoterInterface::ACCESS_GRANTED;
            }
            // group's managers can delete the post
            // if it was marked as spam more than 4 times
            if ($attribute !== self::DELETE || $object->getSpamMarks()->count() >= 4) {
                $group = $object->getGroup();
                if ($group instanceof Group) {
                    return $this->groupVoter->vote($token, $group, [GroupVoter::MANAGE]);
                }
            }
        }

        return VoterInterface::ACCESS_DENIED;
    }
}