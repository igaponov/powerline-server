<?php
namespace Civix\CoreBundle\Event;

class UserPetitionEvents
{
    const PETITION_PRE_CREATE = 'micropetition.petition.pre_create';
    const PETITION_CREATE = 'micropetition.petition.create';
    const PETITION_POST_CREATE = 'micropetition.petition.post_create';
    const PETITION_UPDATE = 'micropetition.petition.update';
    const PETITION_SIGN = 'micropetition.petition.sign';
    const PETITION_UNSIGN = 'micropetition.petition.unsign';
    const PETITION_BOOST = 'micropetition.petition.boost';
    const PETITION_SHARE = 'micropetition.petition.share';
}