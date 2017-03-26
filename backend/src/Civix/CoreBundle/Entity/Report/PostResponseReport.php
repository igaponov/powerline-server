<?php

namespace Civix\CoreBundle\Entity\Report;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="post_response_report", options={"charset"="utf8mb4", "collate"="utf8mb4_unicode_ci", "row_format"="DYNAMIC"})
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Report\PostResponseRepository")
 */
class PostResponseReport
{
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\Column(name="user_id", type="integer", length=11)
     */
    private $user;

    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\Column(name="post_id", type="integer", length=11)
     */
    private $post;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $vote;

    public function __construct(int $user, int $post, string $vote)
    {
        $this->user = $user;
        $this->post = $post;
        $this->vote = $vote;
    }

    /**
     * @return int
     */
    public function getUser(): int
    {
        return $this->user;
    }

    /**
     * @return int
     */
    public function getPost(): int
    {
        return $this->post;
    }

    /**
     * @return string
     */
    public function getVote(): string
    {
        return $this->vote;
    }
}