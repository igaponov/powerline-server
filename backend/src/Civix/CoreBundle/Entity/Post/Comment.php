<?php

namespace Civix\CoreBundle\Entity\Post;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\CommentedInterface;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Post\CommentRepository")
 * @ORM\Table(name="post_comments")
 * @Serializer\ExclusionPolicy("all")
 */
class Comment extends BaseComment
{
    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Post", inversedBy="comments")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $post;

    public function __construct(User $user, Comment $parentComment = null)
    {
        parent::__construct($user, $parentComment);
    }

    /**
     * Set a post.
     *
     * @param Post $post
     * 
     * @return Comment
     */
    public function setPost(Post $post): Comment
    {
        $this->post = $post;

        return $this;
    }

    /**
     * Get post.
     *
     * @return Post
     */
    public function getPost(): Post
    {
        return $this->post;
    }

    public function getCommentedEntity(): CommentedInterface
    {
        return $this->getPost();
    }

    public function getEntityType(): string
    {
        return 'post';
    }
}
