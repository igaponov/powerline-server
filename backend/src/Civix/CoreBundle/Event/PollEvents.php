<?php
namespace Civix\CoreBundle\Event;

class PollEvents
{
    const QUESTION_PRE_CREATE = 'poll.question.pre_create';
    const QUESTION_CREATE = 'poll.question.create';
    const QUESTION_PUBLISHED = 'poll.question.published';
    const QUESTION_ANSWER = 'poll.question.answer';
    const QUESTION_CHANGE_ANSWER = 'poll.question.change_answer';
    const QUESTION_CHARGE = 'poll.question.charge';
}