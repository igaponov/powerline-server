<?php

namespace Civix\CoreBundle\Entity\UserPetition;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\CommentedInterface;
use Civix\CoreBundle\Entity\UserPetition;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Micropetition comments entity.
 *
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\UserPetition\CommentRepository")
 * @ORM\Table(name="user_petition_comments")
 * @Serializer\ExclusionPolicy("all")
 */
class Comment extends BaseComment
{
    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\UserPetition", inversedBy="comments")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $petition;

    /**
     * Set a petition.
     *
     * @param UserPetition $petition
     * 
     * @return Comment
     */
    public function setPetition(UserPetition $petition): Comment
    {
        $this->petition = $petition;

        return $this;
    }

    /**
     * Get micropetition.
     *
     * @return UserPetition
     */
    public function getPetition(): UserPetition
    {
        return $this->petition;
    }

    public function getCommentedEntity(): CommentedInterface
    {
        return $this->getPetition();
    }

    public function getEntityType(): string
    {
        return 'petition';
    }
}
