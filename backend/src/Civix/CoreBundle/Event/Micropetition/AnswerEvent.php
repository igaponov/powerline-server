<?php
namespace Civix\CoreBundle\Event\Micropetition;

use Civix\CoreBundle\Entity\Micropetitions\Answer;
use Symfony\Component\EventDispatcher\Event;

class AnswerEvent extends Event
{
    /**
     * @var Answer
     */
    private $answer;

    public function __construct(Answer $answer)
    {
        $this->answer = $answer;
    }

    /**
     * @return Answer
     */
    public function getAnswer()
    {
        return $this->answer;
    }
}