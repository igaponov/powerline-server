<?php

namespace Civix\CoreBundle\Event;

class PostEvents
{
    const POST_PRE_CREATE = 'post.pre_create';
    const POST_CREATE = 'post.create';
    const POST_UPDATE = 'post.update';
    const POST_SIGN = 'post.sign';
    const POST_UNSIGN = 'post.unsign';
    const POST_BOOST = 'post.boost';
}