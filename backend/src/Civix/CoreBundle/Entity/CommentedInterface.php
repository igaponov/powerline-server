<?php
namespace Civix\CoreBundle\Entity;

use Doctrine\Common\Collections\Collection;

interface CommentedInterface
{
    /**
     * @return integer
     */
    public function getId();

    /**
     * Add comment
     *
     * @param BaseComment $comment
     * @return $this
     */
    public function addComment(BaseComment $comment);

    /**
     * Remove comment
     *
     * @param BaseComment $comment
     */
    public function removeComment(BaseComment $comment);

    /**
     * Get comments
     *
     * @return Collection|BaseComment[]
     */
    public function getComments();
}