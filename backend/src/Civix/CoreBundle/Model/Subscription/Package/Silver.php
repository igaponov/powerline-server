<?php

namespace Civix\CoreBundle\Model\Subscription\Package;

class Silver extends Package
{
    public function getInviteByEmailLimitation()
    {
        return;
    }

    public function getGroupDivisionLimitation()
    {
        return;
    }

    public function getAnnouncementLimitation()
    {
        return 50;
    }

    public function getMicropetitionLimitation()
    {
        return;
    }

    public function getGroupSizeLimitation()
    {
        return 1000;
    }

    public function getSumForPetitionInvites()
    {
        return 15;
    }
}
