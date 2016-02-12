<?php

namespace Civix\CoreBundle\Model\Subscription\Package;

class Gold extends Package
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
        return;
    }

    public function getMicropetitionLimitation()
    {
        return;
    }

    public function getGroupSizeLimitation()
    {
        return 5000;
    }
}
