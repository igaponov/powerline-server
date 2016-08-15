<?php
namespace Civix\CoreBundle\Event\Post;

use Civix\CoreBundle\Entity\Post\Vote;
use Symfony\Component\EventDispatcher\Event;

class AnswerEvent extends Event
{
    /**
     * @var Vote
     */
    private $answer;

    public function __construct(Vote $answer)
    {
        $this->answer = $answer;
    }

    /**
     * @return Vote
     */
    public function getAnswer()
    {
        return $this->answer;
    }
}