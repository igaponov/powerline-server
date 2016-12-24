<?php
namespace Civix\CoreBundle\Event;

class GroupEvents
{
    const CREATED = 'group.created';
    const REGISTERED = 'group.registered';
    const USER_JOINED = 'group.user_joined';
    const USER_BEFORE_UNJOIN = 'group.user_before_unjoin';
    const USER_UNJOIN = 'group.user_unjoin';
    const BEFORE_DELETE = 'group.before_delete';
    const MEMBERSHIP_CONTROL_CHANGED = 'group.membership_control_changed';
    const PERMISSIONS_CHANGED = 'group.permissions_changed';
    const USER_INQUIRED = 'group.user_inquired';
    const BEFORE_AVATAR_DELETE = 'group.before_avatar_delete';
}