<?php
namespace Civix\CoreBundle\Event;

class CommentEvents
{
    const PRE_CREATE = 'comment.pre_create';
    const PRE_UPDATE = 'comment.pre_update';
    const CREATE = 'comment.create';
    const UPDATE = 'comment.update';
    const RATE = 'comment.rate';
}