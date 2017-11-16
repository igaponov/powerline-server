<?php
namespace Civix\CoreBundle\Event;

class PollEvents
{
    public const QUESTION_PRE_CREATE = 'poll.question.pre_create';
    public const QUESTION_CREATE = 'poll.question.create';
    public const QUESTION_PUBLISHED = 'poll.question.published';
    public const QUESTION_ANSWER = 'poll.question.answer';
    public const QUESTION_CHANGE_ANSWER = 'poll.question.change_answer';
    public const QUESTION_CHARGE = 'poll.question.charge';
}