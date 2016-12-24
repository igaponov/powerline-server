<?php
namespace Civix\CoreBundle\Event;

class UserEvents
{
    const REGISTRATION = 'user.registration';
    const FOLLOWED = 'user.followed';
    const BEFORE_AVATAR_DELETE = 'user.before_avatar_delete';
}