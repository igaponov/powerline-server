<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\CiceroRepresentative;

class ProPublicaRepresentativePopulator
{
    public function populate(CiceroRepresentative $representative, array $response): void
    {
        if ($response['date_of_birth'] && !$representative->getBirthday()) {
            $representative->setBirthday(new \DateTime($response['date_of_birth']));
        }
        if ($response['roles'][0]['start_date'] && !$representative->getStartTerm()) {
            $representative->setStartTerm(new \DateTime($response['roles'][0]['start_date']));
        }
        if ($response['roles'][0]['end_date'] && !$representative->getEndTerm()) {
            $representative->setEndTerm(new \DateTime($response['roles'][0]['end_date']));
        }
        if (!$representative->getFacebook()) {
            $representative->setFacebook($response['facebook_account']);
        }
        if (!$representative->getTwitter()) {
            $representative->setTwitter($response['twitter_account']);
        }
        if (!$representative->getYoutube()) {
            $representative->setYoutube($response['youtube_account']);
        }
        $representative->setContactForm($response['roles'][0]['contact_form']);
        $representative->setMissedVotes($response['roles'][0]['missed_votes_pct']);
        $representative->setVotesWithParty($response['roles'][0]['votes_with_party_pct']);
    }
}