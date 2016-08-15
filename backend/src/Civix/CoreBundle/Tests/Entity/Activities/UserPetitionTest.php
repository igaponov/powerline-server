<?php
namespace Civix\CoreBundle\Tests\Entity\Activities;

use Civix\CoreBundle\Entity\Activities\UserPetition;
use Civix\CoreBundle\Entity\Activities\Petition;
use Civix\CoreBundle\Entity\Metadata;

class UserPetitionTest extends \PHPUnit_Framework_TestCase
{
    public function testPetitionClassNameConflict()
    {
        new Petition(); // conflict between Activities\Petition and Micropetitions\Petition
        $microPetition = new UserPetition();
        $petition = new \Civix\CoreBundle\Entity\UserPetition();
        $petition->setMetadata(new Metadata());
        $microPetition->setPetition($petition);
        $this->assertInstanceOf(Metadata::class, $microPetition->getMetadata());
    }
}