<?php
namespace Civix\CoreBundle\Event\Post;

use Civix\CoreBundle\Entity\Post\Vote;
use Symfony\Component\EventDispatcher\Event;

class VoteEvent extends Event
{
    /**
     * @var Vote
     */
    private $vote;

    public function __construct(Vote $vote)
    {
        $this->vote = $vote;
    }

    /**
     * @return Vote
     */
    public function getVote(): Vote
    {
        return $this->vote;
    }
}