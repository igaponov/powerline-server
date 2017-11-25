<?php

namespace Civix\CoreBundle\Entity\UserPetition;

use Civix\CoreBundle\Entity\Post\Vote;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Table(name="user_petition_signatures")
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\UserPetition\SignatureRepository")
 * @Serializer\ExclusionPolicy("all")
 * @Assert\Callback(callback="isPetitionExpired")
 */
class Signature
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-answers-list", "api-leader-answers", "api-petitions-answers", "api-activities"})
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="\Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-leader-answers"})
     */
    protected $user;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-leader-answers", "api-petitions-answers", "api-activities"})
     */
    protected $createdAt;

    /**
     * @var UserPetition
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\UserPetition", inversedBy="signatures")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $petition;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user.
     *
     * @param User $user
     *
     * @return Signature
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set petition.
     *
     * @param UserPetition $petition
     *
     * @return Signature
     */
    public function setPetition(UserPetition $petition)
    {
        $this->petition = $petition;

        return $this;
    }

    /**
     * Get petition.
     *
     * @return UserPetition
     */
    public function getPetition()
    {
        return $this->petition;
    }

    /**
     * @internal
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-answers-list", "api-leader-answers", "api-petitions-answers", "api-activities"})
     *
     * @return mixed
     * @deprecated for consistency with old api, don't user this attribute
     */
    public function getOptionId()
    {
        return Vote::OPTION_UPVOTE;
    }

    public function isPetitionExpired(ExecutionContextInterface $context): void
    {
        $petition = $this->petition;
        if ($petition && $petition->getExpiredAt() < new \DateTime()) {
            $context->addViolation('You could not sign expired petition.');
        }
    }
}
