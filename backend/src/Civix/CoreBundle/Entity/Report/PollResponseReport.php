<?php

namespace Civix\CoreBundle\Entity\Report;

use Civix\CoreBundle\Entity\Poll\Answer;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="poll_response_report", options={"charset"="utf8mb4", "collate"="utf8mb4_unicode_ci", "row_format"="DYNAMIC"})
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Report\PollResponseRepository")
 */
class PollResponseReport
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
     * @ORM\Column(name="poll_id", type="integer", length=11)
     */
    private $poll;

    /**
     * @var int
     *
     * @ORM\Column(name="group_id", type="integer", length=11)
     */
    private $group;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $text;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $answer;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $comment;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    private $privacy;

    public function __construct(int $user, int $poll, int $group, string $text = '', string $answer = '', string $comment = '', int $privacy = Answer::PRIVACY_PUBLIC)
    {
        $this->user = $user;
        $this->poll = $poll;
        $this->group = $group;
        $this->text = $text;
        $this->answer = $answer;
        $this->comment = $comment;
        $this->privacy = $privacy;
    }
}