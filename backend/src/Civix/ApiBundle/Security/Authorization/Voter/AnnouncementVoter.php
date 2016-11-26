<?php
namespace Civix\ApiBundle\Security\Authorization\Voter;

use Civix\CoreBundle\Entity\Announcement;
use Civix\CoreBundle\Entity\LeaderContentRootInterface;
use Civix\CoreBundle\Service\Subscription\PackageHandler;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class AnnouncementVoter implements VoterInterface
{
    const PUBLISH = 'publish';
    /**
     * @var PackageHandler
     */
    private $packageHandler;

    public function __construct(PackageHandler $packageHandler)
    {
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
        return in_array($attribute, array(
            self::PUBLISH,
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
        $supportedClass = Announcement::class;
        return $supportedClass === $class || is_subclass_of($class, $supportedClass);
    }

    /**
     * Returns the vote for the given parameters.
     *
     * This method must return one of the following constants:
     * ACCESS_GRANTED, ACCESS_DENIED, or ACCESS_ABSTAIN.
     *
     * @param TokenInterface $token A TokenInterface instance
     * @param Announcement $object
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

        /** @var LeaderContentRootInterface $user */
        $user = $object->getRoot();

        // check if the given attribute is covered by this voter
        if (!$this->supportsAttribute($attribute)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        if (!$user instanceof LeaderContentRootInterface) {
            return VoterInterface::ACCESS_DENIED;
        }
        
        if ($attribute === self::PUBLISH
            && $this->packageHandler->getPackageStateForAnnouncement($user)->isAllowedWith(1)
        ) {
            return VoterInterface::ACCESS_GRANTED;
        }

        return VoterInterface::ACCESS_DENIED;
    }
}