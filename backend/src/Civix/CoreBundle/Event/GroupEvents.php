<?php
namespace Civix\CoreBundle\Event;

class GroupEvents
{
    const REGISTERED = 'group.registered';
    const USER_JOINED = 'group.user_joined';
    const USER_BEFORE_UNJOIN = 'group.user_before_unjoin';
    const BEFORE_DELETE = 'group.before_delete';
}