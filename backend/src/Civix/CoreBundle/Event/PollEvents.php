<?php
namespace Civix\CoreBundle\Event;

class PollEvents
{
    const QUESTION_CREATE = 'poll.question.create';
    const QUESTION_PUBLISHED = 'poll.question.published';
    const QUESTION_ANSWER = 'poll.question.answer';
}