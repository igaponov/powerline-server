<?php

namespace Civix\ApiBundle\Security\Authorization\Voter;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserInterface;
use Civix\CoreBundle\Service\Subscription\PackageHandler;
use Civix\CoreBundle\Service\Subscription\SubscriptionManager;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\Role\RoleInterface;

class GroupVoter implements VoterInterface
{
    /**
     * Content creators (meta attribute)
     */
    const EDIT = 'edit';
    /**
     * Content creators (assign managers)
     */
    const ASSIGN = 'assign';
    /**
     * Content creators and group managers (create/update/delete)
     */
    const MANAGE = 'manage';
    /**
     * Group members + all above (comments/answers)
     */
    const MEMBER = 'member';
    /**
     * All above
     */
    const VIEW = 'view';

    /**
     * Group managers (check subscription plan)
     */
    const MEMBERSHIP = 'membership';

    /**
     * Group managers (check package limits)
     */
    const MICROPETITION_CONFIG = 'micropetition_config';

    /**
     * @var SubscriptionManager
     */
    private $subscriptionManager;
    /**
     * @var PackageHandler
     */
    private $packageHandler;

    public function __construct(
        SubscriptionManager $subscriptionManager,
        PackageHandler $packageHandler
    ) {
        $this->subscriptionManager = $subscriptionManager;
        $this->packageHandler = $packageHandler;
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
        return in_array($attribute, [
            self::EDIT,
            self::MEMBERSHIP,
            self::ASSIGN,
            self::MANAGE,
            self::MEMBER,
            self::VIEW,
            self::MICROPETITION_CONFIG,
        ]);
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
        $supportedClass = Group::class;
        return $supportedClass === $class || is_subclass_of($class, $supportedClass);
    }

    /**
     * Returns the vote for the given parameters.
     *
     * This method must return one of the following constants:
     * ACCESS_GRANTED, ACCESS_DENIED, or ACCESS_ABSTAIN.
     *
     * @param TokenInterface $token A TokenInterface instance
     * @param Group $object
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

        /** @var UserInterface $user */
        $user = $token->getUser(); // get current logged in user

        // check if the given attribute is covered by this voter
        if (!$this->supportsAttribute($attribute)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        // make sure there is a user object (i.e. that the user is logged in)
        if (!$user instanceof UserInterface) {
            return VoterInterface::ACCESS_DENIED;
        }

        if ($attribute === self::MEMBERSHIP
            && !$this->subscriptionManager->getSubscription($object)->isNotFree()
        ) {
            return VoterInterface::ACCESS_DENIED;
        } elseif ($attribute === self::MICROPETITION_CONFIG
            && !$this->packageHandler->getPackageStateForMicropetition($object)->isAllowed()
        ) {
            return VoterInterface::ACCESS_DENIED;
        }

        $roleHierarchy = new RoleHierarchy([
            self::EDIT => [self::ASSIGN],
            self::ASSIGN => [self::MANAGE],
            self::MANAGE => [self::MEMBER, self::MEMBERSHIP, self::MICROPETITION_CONFIG],
            self::MEMBER => [self::VIEW],
        ]);

        if ($object->isOwner($user)) {
            $userRole = self::EDIT;
        } elseif ($object->isManager($user)) {
            $userRole = self::MANAGE;
        } elseif ($object->isMember($user)) {
            $userRole = self::MEMBER;
        } else {
            return VoterInterface::ACCESS_DENIED;
        }

        /** @var RoleInterface[] $roles */
        $roles = $roleHierarchy->getReachableRoles([new Role($userRole)]);

        foreach ($roles as $role) {
            if ($attribute === $role->getRole()) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        return VoterInterface::ACCESS_DENIED;
    }
}