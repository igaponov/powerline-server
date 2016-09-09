<?php
namespace Civix\ApiBundle\Security\Authorization\Voter;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Poll;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Entity\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class CommentVoter implements VoterInterface
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';

    /**
     * @var LeaderContentVoter
     */
    private $leaderContentVoter;
    /**
     * @var PostVoter
     */
    private $postVoter;
    /**
     * @var PetitionVoter
     */
    private $petitionVoter;

    public function __construct(
        LeaderContentVoter $leaderContentVoter,
        PostVoter $postVoter,
        PetitionVoter $petitionVoter
    )
    {
        $this->leaderContentVoter = $leaderContentVoter;
        $this->postVoter = $postVoter;
        $this->petitionVoter = $petitionVoter;
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
        $supportedClass = BaseComment::class;
        return $supportedClass === $class || is_subclass_of($class, $supportedClass);
    }

    /**
     * Returns the vote for the given parameters.
     *
     * This method must return one of the following constants:
     * ACCESS_GRANTED, ACCESS_DENIED, or ACCESS_ABSTAIN.
     *
     * @param TokenInterface $token A TokenInterface instance
     * @param BaseComment $object
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

        // make sure entity has owner attached to it
        if (!$object->getUser() instanceof UserInterface) {
            return VoterInterface::ACCESS_DENIED;
        }

        if ($attribute === self::VIEW) {
            if ($object instanceof Poll\Comment) {
                $voter = $this->leaderContentVoter;
            } elseif ($object instanceof Post\Comment) {
                $voter = $this->postVoter;
            } elseif ($object instanceof UserPetition\Comment) {
                $voter = $this->petitionVoter;
            } else {
                throw new \RuntimeException('Unsupported object for attribute "view" of CommentVoter');
            }

            return $voter->vote($token, $object->getCommentedEntity(), $attributes);
        }

        if ($object->getUser()->isEqualTo($user)) {
            return VoterInterface::ACCESS_GRANTED;
        }

        return VoterInterface::ACCESS_DENIED;
    }
}