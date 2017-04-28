<?php
namespace Civix\CoreBundle\Event;

class UserEvents
{
    const REGISTRATION = 'user.registration';
    const ADDRESS_CHANGE = 'user.address.change';
    const FOLLOW = 'user.follow';
    const UNFOLLOW = 'user.unfollow';
    const FOLLOW_REQUEST_APPROVE = 'user.follow_request.approve';
    const VIEW_REPRESENTATIVES = 'user.view_representatives';
    const PROFILE_UPDATE = 'user.profile.update';
}