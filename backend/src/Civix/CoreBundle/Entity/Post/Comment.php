<?php

namespace Civix\CoreBundle\Entity\Post;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Post;
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
     * @ORM\ManyToOne(targetEntity="Comment", inversedBy="childrenComments")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $parentComment;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="parentComment")
     */
    protected $childrenComments;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Post", inversedBy="comments")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $post;

    /**
     * Set a post.
     *
     * @param Post $post
     * 
     * @return Comment
     */
    public function setPost(Post $post)
    {
        $this->post = $post;

        return $this;
    }

    /**
     * Get post.
     *
     * @return Post
     */
    public function getPost()
    {
        return $this->post;
    }
}
