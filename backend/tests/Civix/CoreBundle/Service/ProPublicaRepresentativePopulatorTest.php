<?php

namespace Tests\Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\CiceroRepresentative;
use Civix\CoreBundle\Service\ProPublicaRepresentativePopulator;
use PHPUnit\Framework\TestCase;

class ProPublicaRepresentativePopulatorTest extends TestCase
{
    public function testPopulate()
    {
        $representative = new CiceroRepresentative();
        $representative->setBioguide('M000639');
        $response = json_decode(file_get_contents(__DIR__.'/../data/propublica_member.json'), true);
        $populator = new ProPublicaRepresentativePopulator();
        $populator->populate($representative, $response['results'][0]);
        $this->assertSame('1966-03-01', $representative->getBirthday()->format('Y-m-d'));
        $this->assertSame('2017-01-03', $representative->getStartTerm()->format('Y-m-d'));
        $this->assertSame('2019-01-03', $representative->getEndTerm()->format('Y-m-d'));
        $this->assertSame('reptrentkelly', $representative->getFacebook());
        $this->assertSame('reptrentkelly', $representative->getTwitter());
        $this->assertSame('reptrentkelly', $representative->getYoutube());
        $this->assertSame(
            'http://www.senate.gov/public/index.cfm?p=Email',
            $representative->getContactForm()
        );
        $this->assertSame(2.85, $representative->getMissedVotes());
        $this->assertSame(98.17, $representative->getVotesWithParty());
    }
}
