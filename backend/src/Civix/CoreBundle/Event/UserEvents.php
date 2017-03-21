<?php
namespace Civix\CoreBundle\Event;

class UserEvents
{
    const REGISTRATION = 'user.registration';
    const FOLLOW = 'user.follow';
    const UNFOLLOW = 'user.unfollow';
    const FOLLOW_REQUEST_APPROVE = 'user.follow_request.approve';
}