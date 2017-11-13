<?php

namespace Civix\CoreBundle\Event;

class PostEvents
{
    const POST_PRE_CREATE = 'post.pre_create';
    const POST_CREATE = 'post.create';
    const POST_POST_CREATE = 'post.post_create';
    const POST_UPDATE = 'post.update';
    const POST_VOTE = 'post.vote';
    const POST_PRE_UNVOTE = 'post.pre_unvote';
    const POST_UNVOTE = 'post.unvote';
    const POST_BOOST = 'post.boost';
    const POST_SHARE = 'post.share';
}