<?php
namespace Civix\CoreBundle\Event;

class CommentEvents
{
    const CREATE = 'comment.create';
    const UPDATE = 'comment.update';
    const RATE = 'comment.rate';
    const UPDATE_RATE = 'comment.update_rate';
}